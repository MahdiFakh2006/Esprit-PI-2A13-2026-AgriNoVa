<?php
/**
 * controllers/AnalyseController.php
 * Module 3 – Diagnostic IA : l'agriculteur envoie la photo d'une feuille,
 * le serveur l'analyse (couleurs des pixels) et propose la maladie la plus probable.
 *
 * Prototype « analyse d'image » : chaque pixel de la feuille est classé
 * (vert = sain, jaune, brun, blanc, noir = symptômes). La couleur dominante des symptômes
 * est comparée aux maladies connues pour la culture choisie.
 * Le résultat est indicatif et ne remplace pas l'avis d'un agronome.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/MaladieController.php';
require_once __DIR__ . '/../models/AnalyseModel.php';

class AnalyseController
{
    public const MAX_SIZE        = 5 * 1024 * 1024;   // 5 Mo
    public const MAX_PAR_JOUR    = 10;                // anti-abus : analyses par agriculteur et par jour
    public const SEUIL_CONFIANCE = 60;                // en dessous : on conseille un expert (support M5)
    private const MIN_FEUILLE    = 10;                // % minimum de l'image occupé par la feuille
    private const UPLOAD_DIR     = __DIR__ . '/../uploads/analyses/';
    private const TYPES          = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    public const PER_PAGE        = 10;

    private PDO $db;
    private MaladieController $maladies;

    public function __construct(MaladieController $maladies)
    {
        $this->db = Database::getConnexion();
        $this->maladies = $maladies;
    }

    private const SELECT = 'SELECT a.*, m.nom AS nom_maladie, m.traitement, m.gravite,
                                   CONCAT(u.prenom, \' \', u.nom) AS nom_agriculteur
                            FROM analyse a
                            LEFT JOIN maladie m ON a.id_maladie = m.id_maladie
                            JOIN utilisateur u ON a.id_agriculteur = u.id_utilisateur';

    // ==============================================================
    // LECTURE
    // ==============================================================

    public function find(int $id): ?AnalyseModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE a.id_analyse = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? AnalyseModel::fromArray($row) : null;
    }

    /** Règle métier : un agriculteur ne voit QUE ses propres analyses. */
    public function findForOwner(int $id, int $agriculteurId): ?AnalyseModel
    {
        $a = $this->find($id);
        return ($a && $a->getIdAgriculteur() === $agriculteurId) ? $a : null;
    }

    /** @return AnalyseModel[] historique de l'agriculteur, du plus récent au plus ancien */
    public function mesAnalyses(int $agriculteurId): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE a.id_agriculteur = :id ORDER BY a.date_analyse DESC, a.id_analyse DESC');
        $stmt->execute(['id' => $agriculteurId]);
        return array_map([AnalyseModel::class, 'fromArray'], $stmt->fetchAll());
    }

    /** Back Office : toutes les analyses, avec filtres et pagination. */
    public function search(array $query): array
    {
        $culture = (string) ($query['culture'] ?? '');
        $etat    = (string) ($query['etat'] ?? '');
        $page    = max(1, (int) ($query['page'] ?? 1));
        $where = [];
        $params = [];
        if ($culture !== '' && in_array($culture, $this->maladies->cultures(), true)) {
            $where[] = 'a.culture = :culture';
            $params['culture'] = $culture;
        } else {
            $culture = '';
        }
        $etats = [
            'malade'  => 'a.id_maladie IS NOT NULL',
            'saine'   => 'a.id_maladie IS NULL AND a.taux_atteinte < ' . AnalyseModel::SEUIL_SAINE,
            'douteux' => 'a.confiance < ' . self::SEUIL_CONFIANCE,
        ];
        if (array_key_exists($etat, $etats)) {
            $where[] = $etats[$etat];   // condition prise dans une liste blanche
        } else {
            $etat = '';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $this->db->prepare('SELECT COUNT(*) FROM analyse a' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $stmt = $this->db->prepare(self::SELECT . $whereSql . ' ORDER BY a.date_analyse DESC, a.id_analyse DESC LIMIT '
            . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE));
        $stmt->execute($params);
        $analyses = array_map([AnalyseModel::class, 'fromArray'], $stmt->fetchAll());
        return compact('analyses', 'total', 'page', 'pages', 'culture', 'etat');
    }

    /** Statistiques du Back Office : maladies les plus détectées, par culture, taux de feuilles saines. */
    public function stats(): array
    {
        $total   = (int) $this->db->query('SELECT COUNT(*) FROM analyse')->fetchColumn();
        $saines  = (int) $this->db->query('SELECT COUNT(*) FROM analyse WHERE id_maladie IS NULL AND taux_atteinte < ' . AnalyseModel::SEUIL_SAINE)->fetchColumn();
        $douteux = (int) $this->db->query('SELECT COUNT(*) FROM analyse WHERE confiance < ' . self::SEUIL_CONFIANCE)->fetchColumn();
        $top = $this->db->query(
            'SELECT m.nom, m.culture, COUNT(*) AS nb
             FROM analyse a JOIN maladie m ON a.id_maladie = m.id_maladie
             GROUP BY m.id_maladie, m.nom, m.culture ORDER BY nb DESC LIMIT 5'
        )->fetchAll();
        $parCulture = $this->db->query(
            'SELECT culture, COUNT(*) AS nb FROM analyse GROUP BY culture ORDER BY nb DESC'
        )->fetchAll();
        return compact('total', 'saines', 'douteux', 'top', 'parCulture');
    }

    // ==============================================================
    // CRÉATION : envoi de la photo + diagnostic
    // ==============================================================

    /** @return array [erreurs par champ, id de l'analyse créée ou 0] */
    public function create(int $agriculteurId, array $post, array $files): array
    {
        if (!AuthController::verifyCsrfToken($post['csrf_token'] ?? null)) {
            return [['global' => 'Jeton de sécurité invalide. Rechargez la page et réessayez.'], 0];
        }
        $errors = [];

        // Règle métier : 10 analyses maximum par jour et par agriculteur
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM analyse WHERE id_agriculteur = :id AND date_analyse >= :jour');
        $stmt->execute(['id' => $agriculteurId, 'jour' => date('Y-m-d 00:00:00')]);
        if ((int) $stmt->fetchColumn() >= self::MAX_PAR_JOUR) {
            return [['global' => 'Limite atteinte : ' . self::MAX_PAR_JOUR . ' analyses par jour. Revenez demain.'], 0];
        }

        $culture = trim((string) ($post['culture'] ?? ''));
        if (!in_array($culture, $this->maladies->cultures(), true)) {
            $errors['culture'] = 'Choisissez la culture dans la liste.';
        }

        $file = $files['image'] ?? null;
        $ext = null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['image'] = 'Ajoutez la photo d’une feuille.';
        } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > self::MAX_SIZE) {
            $errors['image'] = 'Image trop lourde : 5 Mo maximum.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $errors['image'] = 'L’envoi de l’image a échoué. Réessayez.';
        } else {
            // On vérifie le VRAI contenu du fichier, pas seulement son extension
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $size = @getimagesize($file['tmp_name']);
            if (!isset(self::TYPES[$mime]) || $size === false) {
                $errors['image'] = 'Format non accepté : uniquement JPG ou PNG.';
            } elseif ($size[0] < 100 || $size[1] < 100) {
                $errors['image'] = 'Image trop petite : 100 × 100 pixels minimum.';
            } else {
                $ext = self::TYPES[$mime];
            }
        }
        if ($errors) {
            return [$errors, 0];
        }

        // Nom de fichier aléatoire : impossible de deviner ou d'écraser la photo d'un autre
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0775, true);
        }
        $nomFichier = bin2hex(random_bytes(16)) . '.' . $ext;
        $chemin = self::UPLOAD_DIR . $nomFichier;
        if (!move_uploaded_file($file['tmp_name'], $chemin)) {
            return [['global' => 'Impossible d’enregistrer l’image sur le serveur.'], 0];
        }

        $mesures = self::analyserImage($chemin);
        if ($mesures === null || $mesures['feuille'] < self::MIN_FEUILLE) {
            @unlink($chemin);
            return [['image' => 'Aucune feuille détectée : photographiez une seule feuille, en gros plan, sur un fond uni.'], 0];
        }

        $diag = $this->diagnostiquer($culture, $mesures);
        $this->db->prepare(
            'INSERT INTO analyse (id_maladie, id_agriculteur, culture, image, date_analyse, confiance, taux_atteinte, statut)
             VALUES (:maladie, :agriculteur, :culture, :image, :date, :confiance, :taux, \'terminee\')'
        )->execute([
            'maladie'     => $diag['id_maladie'],
            'agriculteur' => $agriculteurId,
            'culture'     => $culture,
            'image'       => $nomFichier,
            'date'        => date('Y-m-d H:i:s'),
            'confiance'   => $diag['confiance'],
            'taux'        => $mesures['atteinte'],
        ]);
        return [[], (int) $this->db->lastInsertId()];
    }

    /**
     * Choisit la maladie la plus probable à partir des couleurs mesurées.
     * @return array ['id_maladie' => ?int, 'confiance' => float]
     */
    public function diagnostiquer(string $culture, array $mesures): array
    {
        $atteinte = $mesures['atteinte'];
        if ($atteinte < AnalyseModel::SEUIL_SAINE) {
            // Feuille saine : plus il y a de taches, moins on est sûr
            return ['id_maladie' => null, 'confiance' => (float) max(60, round(100 - $atteinte * 4))];
        }

        $symptomes = ['jaune' => $mesures['jaune'], 'brun' => $mesures['brun'], 'blanc' => $mesures['blanc'], 'noir' => $mesures['noir']];
        arsort($symptomes);
        $couleur = array_key_first($symptomes);
        $part = $symptomes[$couleur] / max(0.01, $atteinte);   // part de la couleur dominante dans les taches

        // Maladies de CETTE culture qui donnent CETTE couleur (jointure logique couleur → maladie)
        $ordre = $atteinte >= 30 ? "FIELD(gravite, 'elevee', 'moyenne', 'faible')" : "FIELD(gravite, 'faible', 'moyenne', 'elevee')";
        $stmt = $this->db->prepare(
            "SELECT id_maladie FROM maladie WHERE culture = :culture AND couleur_symptome = :couleur ORDER BY $ordre, id_maladie"
        );
        $stmt->execute(['culture' => $culture, 'couleur' => $couleur]);
        $candidats = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$candidats) {
            // Symptômes présents mais aucune maladie connue : confiance basse → conseil d'expert
            return ['id_maladie' => null, 'confiance' => 30.0];
        }
        // Une seule maladie possible : confiance élevée ; plusieurs : la confiance est partagée
        $confiance = min(95, round((35 + 60 * $part) / count($candidats), 1));
        return ['id_maladie' => (int) $candidats[0], 'confiance' => (float) $confiance];
    }

    /**
     * Analyse de l'image avec GD : classe chaque pixel de la feuille.
     * Le blanc et le noir ne comptent qu'À L'INTÉRIEUR de la feuille (entre ses bords, ligne par ligne),
     * pour ne pas confondre un fond blanc ou noir avec de l'oïdium ou de la fumagine.
     * @return array|null pourcentages : feuille (part de l'image), atteinte, sain, jaune, brun, blanc, noir
     */
    public static function analyserImage(string $chemin): ?array
    {
        if (!extension_loaded('gd')) {
            return null;
        }
        $info = @getimagesize($chemin);
        $src = match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($chemin),
            IMAGETYPE_PNG  => @imagecreatefrompng($chemin),
            default        => false,
        };
        if (!$src) {
            return null;
        }
        $img = imagescale($src, 160);   // image réduite : rapide et suffisant pour compter les couleurs
        imagedestroy($src);
        $w = imagesx($img);
        $h = imagesy($img);

        $compte = ['sain' => 0, 'jaune' => 0, 'brun' => 0, 'blanc' => 0, 'noir' => 0];
        for ($y = 0; $y < $h; $y++) {
            $ligne = [];
            $premier = -1;
            $dernier = -1;
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $c = self::classerPixel(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
                $ligne[$x] = $c;
                if ($c === 'sain' || $c === 'jaune' || $c === 'brun') {
                    if ($premier < 0) {
                        $premier = $x;
                    }
                    $dernier = $x;
                }
            }
            for ($x = $premier; $premier >= 0 && $x <= $dernier; $x++) {
                if (isset($compte[$ligne[$x]])) {
                    $compte[$ligne[$x]]++;
                }
            }
        }
        imagedestroy($img);

        $feuille = array_sum($compte);
        if ($feuille === 0) {
            return ['feuille' => 0.0, 'atteinte' => 0.0, 'sain' => 0.0, 'jaune' => 0.0, 'brun' => 0.0, 'blanc' => 0.0, 'noir' => 0.0];
        }
        $pct = fn(int $n) => round(100 * $n / $feuille, 2);
        return [
            'feuille'  => round(100 * $feuille / ($w * $h), 2),
            'atteinte' => $pct($feuille - $compte['sain']),
            'sain'     => $pct($compte['sain']),
            'jaune'    => $pct($compte['jaune']),
            'brun'     => $pct($compte['brun']),
            'blanc'    => $pct($compte['blanc']),
            'noir'     => $pct($compte['noir']),
        ];
    }

    /** Classe un pixel selon sa teinte (HSV). Mêmes seuils que assets/js/diagnostic.js. */
    public static function classerPixel(int $r, int $g, int $b): string
    {
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $v = $max / 255;
        $s = $max === 0 ? 0 : ($max - $min) / $max;
        if ($max === $min) {
            $hue = 0;
        } elseif ($max === $r) {
            $hue = fmod(60 * (($g - $b) / ($max - $min)) + 360, 360);
        } elseif ($max === $g) {
            $hue = 60 * (($b - $r) / ($max - $min)) + 120;
        } else {
            $hue = 60 * (($r - $g) / ($max - $min)) + 240;
        }

        if ($v < 0.22) {
            return 'noir';
        }
        if ($v > 0.78 && $s < 0.18) {
            return 'blanc';
        }
        if ($hue >= 70 && $hue <= 170 && $s > 0.18) {
            return 'sain';
        }
        if ($hue >= 42 && $hue < 70 && $s > 0.30 && $v > 0.40) {
            return 'jaune';
        }
        if (($hue < 42 || $hue >= 330) && $s > 0.20 && $v <= 0.80) {
            return 'brun';
        }
        return 'autre';
    }

    // ==============================================================
    // SUPPRESSION
    // ==============================================================

    /** L'agriculteur supprime SA propre analyse ; l'admin peut supprimer n'importe laquelle. */
    public function delete(int $id, array $post, ?int $agriculteurId = null): ?string
    {
        if (!AuthController::verifyCsrfToken($post['csrf_token'] ?? null)) {
            return 'Jeton de sécurité invalide.';
        }
        $a = $agriculteurId === null ? $this->find($id) : $this->findForOwner($id, $agriculteurId);
        if ($a === null) {
            return 'Analyse introuvable.';
        }
        $this->db->prepare('DELETE FROM analyse WHERE id_analyse = :id')->execute(['id' => $id]);
        @unlink(self::UPLOAD_DIR . basename($a->getImage()));   // basename : jamais de chemin « ../ »
        return null;
    }
}

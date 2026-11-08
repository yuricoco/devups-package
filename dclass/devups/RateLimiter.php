<?php

/**
 * ETAPE L.1 : limiteur de frequence generique (cle, fenetre, max), en
 * cache fichier - meme pattern que cache/rights/<role_id>.php et
 * cache/lang/active.php (lisible/modifiable sans schema de base, sans
 * migration). Un fichier par cle, sous cache/ratelimit/.
 *
 * Usage :
 *   $limiter = new RateLimiter('login_admin:' . $ip, 900, 5);
 *   if ($limiter->tooManyAttempts()) { ... 429 ... }
 *   // apres un echec :
 *   $limiter->hit();
 *   // apres un succes :
 *   $limiter->clear();
 */
class RateLimiter
{
    private string $file;
    private int $windowSeconds;
    private int $max;

    public function __construct(string $key, int $windowSeconds, int $max)
    {
        $dir = ROOT . 'cache/ratelimit/';
        if (!is_dir($dir))
            @mkdir($dir, 0777, true);
        // .json, pas .php : lu/ecrit via le MEME descripteur de fichier que
        // celui verrouille par hit() (jamais un second fopen()/include()
        // en parallele - sous Windows, un flock() applique au process entier
        // sur le chemin rend un fopen()/include() concurrent du MEME
        // processus inutilisable pendant le verrou, contrairement a Unix).
        $this->file = $dir . sha1($key) . '.json';
        $this->windowSeconds = $windowSeconds;
        $this->max = $max;
    }

    public function tooManyAttempts(): bool
    {
        return $this->read()['attempts'] >= $this->max;
    }

    /** Enregistre une tentative (echec) et retourne le nombre d'essais dans la fenetre courante. */
    public function hit(): int
    {
        $fp = @fopen($this->file, 'c+');
        if ($fp === false) {
            // cache/ illisible/non inscriptible : ne jamais bloquer
            // l'appelant pour un probleme d'infrastructure.
            return 0;
        }
        // Verrou tenu pendant tout le cycle lecture-incrementation-ecriture :
        // sans lui, deux requetes concurrentes pourraient lire le meme
        // compteur et l'incrementer chacune separement, perdant une
        // tentative (contournement possible de la limite).
        flock($fp, LOCK_EX);
        $data = $this->parse(stream_get_contents($fp));
        $data['attempts']++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $this->serialize($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $data['attempts'];
    }

    /** Nombre de secondes avant la reinitialisation de la fenetre courante. */
    public function retryAfter(): int
    {
        $data = $this->read();
        return max(0, $this->windowSeconds - (time() - $data['window_started_at']));
    }

    /** Remet le compteur a zero (succes, nouveau code genere...). */
    public function clear(): void
    {
        if (file_exists($this->file))
            @unlink($this->file);
    }

    private function read(): array
    {
        if (!file_exists($this->file))
            return $this->fresh();
        return $this->parse(@file_get_contents($this->file));
    }

    private function parse(?string $raw): array
    {
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data) || !isset($data['attempts'], $data['window_started_at']))
            return $this->fresh();
        if (time() - $data['window_started_at'] >= $this->windowSeconds)
            return $this->fresh();
        return $data;
    }

    private function fresh(): array
    {
        return ['attempts' => 0, 'window_started_at' => time()];
    }

    private function serialize(array $data): string
    {
        return json_encode($data);
    }
}

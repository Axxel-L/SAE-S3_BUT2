<?php
/**
 * Gestion de la connexion à la BD
 */
class DatabaseConnection {
    private static ?self $instance = null;
    private \PDO $pdo;
    private string $host;
    private string $db;
    private string $user;
    private string $password;
    
    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db = $_ENV['DB_NAME'] ?? 'vote';
        $this->user = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        
        $this->connect();
    }
    
    /**
     * Retourne l'instance unique de connexion (Singleton)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Établit la connexion PDO
     */
    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
            $this->pdo = new \PDO($dsn, $this->user, $this->password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            die("Erreur de connexion BD: " . $e->getMessage());
        }
    }
    
    /**
     * Retourne la connexion PDO
     */
    public function getConnection(): \PDO {
        return $this->pdo;
    }
    
    /**
     * Prépare une requête
     */
    public function prepare(string $sql): \PDOStatement {
        return $this->pdo->prepare($sql);
    }
    
    /**
     * Exécute une requête
     */
    public function query(string $sql): \PDOStatement {
        return $this->pdo->query($sql);
    }
    
    /**
     * Commence une transaction
     */
    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Valide une transaction
     */
    public function commit(): void {
        $this->pdo->commit();
    }
    
    /**
     * Annule une transaction
     */
    public function rollBack(): void {
        $this->pdo->rollBack();
    }
    
    /**
     * Vérifie si une transaction est active
     */
    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Obtient le dernier ID inséré
     */
    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
    
    // Empêche le clonage
    private function __clone() {}
    
    // Empêche la désérialisation
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
?>

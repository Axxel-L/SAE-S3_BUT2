<?php
/**
 * User Model
 * Représente un utilisateur
 * SOLID: Single Responsibility (représentation d'un utilisateur)
 */

class User {
    private ?int $id;
    private string $email;
    private string $pseudo;
    private string $passwordHash;
    private string $salt;
    private string $type; // 'joueur', 'candidat', 'admin'
    private \DateTime $dateInscription;
    
    public function __construct(
        ?int $id = null,
        string $email = '',
        string $pseudo = '',
        string $passwordHash = '',
        string $salt = '',
        string $type = 'joueur'
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->pseudo = $pseudo;
        $this->passwordHash = $passwordHash;
        $this->salt = $salt;
        $this->type = $type;
        $this->dateInscription = new \DateTime();
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPseudo(): string { return $this->pseudo; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getSalt(): string { return $this->salt; }
    public function getType(): string { return $this->type; }
    public function getDateInscription(): \DateTime { return $this->dateInscription; }
    
    // Setters
    public function setEmail(string $email): self {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Email invalide: $email");
        }
        $this->email = $email;
        return $this;
    }
    
    public function setPseudo(string $pseudo): self {
        if (strlen($pseudo) < 3 || strlen($pseudo) > 30) {
            throw new \Exception("Pseudo doit contenir entre 3 et 30 caractères");
        }
        $this->pseudo = $pseudo;
        return $this;
    }
    
    public function setType(string $type): self {
        if (!in_array($type, ['joueur', 'candidat', 'admin'])) {
            throw new \Exception("Type utilisateur invalide: $type");
        }
        $this->type = $type;
        return $this;
    }
    
    public function setPasswordHash(string $hash): self {
        $this->passwordHash = $hash;
        return $this;
    }
    
    public function setSalt(string $salt): self {
        $this->salt = $salt;
        return $this;
    }
    
    /**
     * Convertit l'objet en tableau pour la BD
     */
    public function toArray(): array {
        return [
            'id_utilisateur' => $this->id,
            'email' => $this->email,
            'pseudo' => $this->pseudo,
            'mot_de_passe' => $this->passwordHash,
            'salt' => $this->salt,
            'type' => $this->type,
            'date_inscription' => $this->dateInscription->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Crée un User à partir des données de la BD
     */
    public static function fromDatabase(array $data): self {
        $user = new self(
            $data['id_utilisateur'] ?? null,
            $data['email'] ?? '',
            $data['pseudo'] ?? '',
            $data['mot_de_passe'] ?? '',
            $data['salt'] ?? '',
            $data['type'] ?? 'joueur'
        );
        
        if (isset($data['date_inscription'])) {
            $user->dateInscription = new \DateTime($data['date_inscription']);
        }
        
        return $user;
    }
    
    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool {
        return $this->type === 'admin';
    }
    
    /**
     * Vérifie si l'utilisateur est candidat
     */
    public function isCandidate(): bool {
        return $this->type === 'candidat';
    }
    
    /**
     * Vérifie si l'utilisateur est joueur
     */
    public function isPlayer(): bool {
        return $this->type === 'joueur';
    }
}
?>

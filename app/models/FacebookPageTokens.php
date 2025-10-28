<?php
require_once __DIR__ . '/Model.php';

class FacebookPageTokens extends Model
{
    protected static $table = 'facebook_page_tokens';
    public    static $table_columns = [];
    protected static $basic_columns = ['id', 'authorized_account_id', 'barangay_facebook_page_id', 'page_access_token', 'token_expiry'];

    /** properties */
    protected $authorized_account_id = 0;
    protected $barangay_facebook_page_id = 0;
    protected $page_access_token = '';
    protected $token_expiry = '';

    /**
     * Constructor
     * @param int $id
     */
    public function __construct($id = 0)
    {
        parent::__construct();

        if ($id > 0) {
            $stmt = $this->getConnection()->prepare("SELECT * FROM `" . self::$table . "` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $this->hydrate($row);
            }
        }
    }

    // -------------------- GETTERS --------------------
    public function getAuthorizedAccountId() { return $this->authorized_account_id; }
    public function getBarangayFacebookPageId() {return $this->barangay_facebook_page_id;}
    public function getPageAccessToken() {return $this->page_access_token;}
    public function getTokenExpiry()    { return $this->token_expiry; }

    // -------------------- SETTERS --------------------
    public function setAuthorizedAccountId($authorized_account_id) { $this->authorized_account_id = $authorized_account_id; }
    public function setBarangayFacebookPageId($barangay_facebook_page_id) {$this->barangay_facebook_page_id = $barangay_facebook_page_id;}
    public function setPageAccessToken($page_access_token) { $this->page_access_token = $page_access_token; }
    public function setTokenExpiry($token_expiry)    { $this->token_expiry = $token_expiry; }




    // -------------------- CRUD METHODS --------------------

    /**
     * Insert a Facebook Page Token
     */
    public function insert(): bool
    {
        $stmt = $this->getConnection()->prepare("
            INSERT INTO `" . self::$table . "`
            (`authorized_account_id`, `barangay_facebook_page_id`, `page_access_token`, `token_expiry`)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss",
            $this->authorized_account_id,
            $this->barangay_facebook_page_id,
            $this->page_access_token,
            $this->token_expiry
        );
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $this->setId($stmt->insert_id);
            return true;
        }
        return false;
    }

    /**
     * Update Facebook Page Token
     */
    public function update(): bool
    {
        $stmt = $this->getConnection()->prepare("
            UPDATE `" . self::$table . "`
            SET `authorized_account_id` = ?, `barangay_facebook_page_id` = ?, `page_access_token` = ?, `token_expiry` = ?
            WHERE `id` = ?
        ");
        $stmt->bind_param("iissi",
            $this->authorized_account_id,
            $this->barangay_facebook_page_id,
            $this->page_access_token,
            $this->token_expiry,
            $this->id
        );
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Delete Facebook Page Token
     */
    public function delete(): bool
    {
        $stmt = $this->getConnection()->prepare("DELETE FROM `" . self::$table . "` WHERE `id` = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Fetch all Facebook Page Tokens, or by Token of a Certain Authorized_Account (optional)
     */
    public static function all(bool $assoc = false, bool $assoc_basic = false, ?AuthorizedAccount $account = null): array
    {
        $query = "SELECT * FROM `" . self::$table . "`";
        $params = [];
        $types = '';

        if ($account !== null) {
            $query .= " WHERE `authorized_account_id` = ?";
            $params[] = $account->getId();
            $types .= "i";
        }

        $stmt = self::getConnectionStatic()->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $pages = [];
        while ($row = $result->fetch_assoc()) {
            $pages[] = $row;
        }
        return $pages;
    }
}

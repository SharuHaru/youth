<?php
require_once __DIR__ . '/Model.php';

class BarangayFacebookPages extends Model
{
    protected static $table = 'barangay_facebook_pages';
    public    static $table_columns = [];
    protected static $basic_columns = ['id', 'barangay_id', 'pade_id', 'page_name', 'created_at', 'updated_at'];

    /** properties */
    protected $barangay_id = null;
    protected $page_id = '';
    protected $page_name = '';
    protected $created_at = '';
    protected $updated_at = '';

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
    public function getBarangayId() { return $this->barangay_id; }
    public function getPageId() {return $this->page_id;}
    public function getPageName() {return $this->page_name;}
    public function getCreatedAt()    { return $this->created_at; }
    public function getUpdatedAt()    { return $this->updated_at; }

    // -------------------- SETTERS --------------------
    public function setBarangayId($barangay_id) { $this->barangay_id = $barangay_id; }
    public function setPageId($page_id) {$this->page_id = $page_id;}
    public function setPageName($page_name) { $this->page_name = $page_name; }
    public function setCreatedAt($created_at)    { $this->created_at = $created_at; }
    public function setUpdatedAt($updated_at)    { $this->updated_at = $updated_at; }




    // -------------------- CRUD METHODS --------------------

    /**
     * Insert a Barangay Facebook Page
     */
    public function insert(): bool
    {
        $stmt = $this->getConnection()->prepare("
            INSERT INTO `" . self::$table . "`
            (`barangay_id`, `page_id`, `page_name`)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss",
            $this->barangay_id,
            $this->page_id,
            $this->page_name,
        );
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $this->setId($stmt->insert_id);
            return true;
        }
        return false;
    }

    /**
     * Update Barangay Facebook Page
     */
    public function update(): bool
    {
        $stmt = $this->getConnection()->prepare("
            UPDATE `" . self::$table . "`
            SET `barangay_id` = ?, `page_id` = ?, `page_name` = ?
            WHERE `id` = ?
        ");
        $stmt->bind_param("issi",
            $this->barangay_id,
            $this->page_id,
            $this->page_name,
            $this->id
        );
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Delete Barangay Facebook Page
     */
    public function delete(): bool
    {
        $stmt = $this->getConnection()->prepare("DELETE FROM `" . self::$table . "` WHERE `id` = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Fetch all advocacies, or by Barangay (optional)
     */
    public static function all(bool $assoc = false, bool $assoc_basic = false, ?Barangay $barangay = null): array
    {
        $query = "SELECT * FROM `" . self::$table . "`";
        $params = [];
        $types = '';

        if ($barangay !== null) {
            $query .= " WHERE `barangay_id` = ?";
            $params[] = $barangay->getId();
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

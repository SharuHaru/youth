<?php
require_once __DIR__ . '/Model.php';

class PasswordResets extends Model {

    //Static Data
    protected static $table = 'password_resets';
    public    static $table_columns = [];
    protected static $basic_columns = ['id', 'authorized_account_id', 'otp', 'expires_at', 'used'];

    // Properties
    protected $id = 0;
    protected $authorized_account_id = 0;
    protected $otp = null;
    protected $expires_at = null;
    protected $used = 0;

        /**
     * Constructor
     * @param $id
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
    public function getAuthorizedAccountId()  { return $this->authorized_account_id; }
    public function getOtp()                  { return $this->otp; }
    public function getExpiresAt()            { return $this->expires_at; }
    public function getUsed()                 { return $this->used; }

    // -------------------- SETTERS --------------------
    public function setAuthorizedAccountId($authorized_account_id)  { $this->authorized_account_id = $authorized_account_id; }
    public function setOtp($otp)                                    { $this->otp = $otp; }
    public function setExpiresAt($expires_at)                       { $this->expires_at = $expires_at; }
    public function setUsed($used)                                  { $this->used = $used; }




    // -------------------- CRUD OPERATIONS --------------------

    // Insert password reset record
    public function insert(): bool {
        $stmt = $this->getConnection()->prepare("INSERT INTO `" . self::$table . "` (`authorized_account_id`, `otp`, `expires_at`, `used`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $this->authorized_account_id, $this->otp, $this->expires_at, $this->used);
        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return true;
        }
        return false;
    }

    /**
     * Update password reset record
     */
    public function update(): bool {
        $stmt = $this->getConnection()->prepare("
            UPDATE `" . self::$table . "` 
            SET `authorized_account_id` = ?, `otp` = ?, `expires_at` = ?, `used` = ?
            WHERE `id` = ?
        ");
        $stmt->bind_param("issii",
            $this->authorized_account_id,
            $this->otp,
            $this->expires_at,
            $this->used,
            $this->id
        );
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Delete password reset record
     */
    public function delete(): bool {
        $stmt = $this->getConnection()->prepare("
            DELETE FROM `" . self::$table . "` WHERE `id` = ?
        ");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Fetch all password reset records, optionally filtered by AuthorizedAccount
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

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $reset = new PasswordResets();
            $reset->hydrate($row);
            $records[] = $assoc ? $reset->getAssoc($assoc_basic) : $reset;
        }

        return $records;
    }



    // -------------------- CUSTOM METHODS --------------------

    /**
     * Mark all previous OTPs for this user as used
     */
    public static function markOldOtpsAsUsed(int $authorized_account_id): bool
    {
        try {
            $conn = self::getConnectionStatic(); // ✅ Correct static connection
            $stmt = $conn->prepare("
                UPDATE `" . self::$table . "` 
                SET `used` = 1 
                WHERE `authorized_account_id` = ?
            ");
            $stmt->bind_param("i", $authorized_account_id);
            $stmt->execute();

            $affected = $stmt->affected_rows;
            $stmt->close();

            // Return true even if no rows were updated
            return $affected >= 0;

        } catch (Exception $e) {
            error_log("Error marking old OTPs as used: " . $e->getMessage());
            return false;
        }
    }

    public static function findActiveOtp(int $authorized_account_id, string $otp)
    {
        $conn = self::getConnectionStatic();
        $stmt = $conn->prepare("
            SELECT * FROM `" . self::$table . "`
            WHERE `authorized_account_id` = ? 
            AND `otp` = ? 
            AND `used` = 0
            LIMIT 1
        ");
        $stmt->bind_param("is", $authorized_account_id, $otp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $reset = new self();
            $reset->hydrate($row);
            return $reset;
        }

        return null;
    }

}
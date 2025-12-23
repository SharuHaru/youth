<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/AnnouncementImage.php';
require_once __DIR__ .  '/AnnouncementDateTime.php';
require_once __DIR__ . '/BarangayFacebookPages.php';
require_once __DIR__ . '/FacebookPageTokens.php';


class Announcement extends Model
{
    /** static data */
    public    static $table         = 'announcements';
    public    static $table_columns = [];
    protected static $basic_columns = ['id', 'title', 'date', 'is_featured'];

    /** properties */
    protected $barangay_id = 0;
    protected $title       = '';
    // Announcement.php (class props)
    protected ?int $thumbnail_id = null;
    protected $description = '';
    protected $is_featured = 0;
    protected $what = '';
    protected $who = '';
    protected $why = '';
    protected $where = '';
    protected $facebook_post_id = '';
    protected $facebook_object_id = '';
    

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
    /**
     * Gets Announcement barangay_id.
     * @return int
     */
    public function getBarangayId()
    {
        return $this->barangay_id;
    }


    /**
     * Gets Announcement title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets Announcement thumbnail_id
     * @return int
     */
    public function getThumbnailId()
    {
        return $this->thumbnail_id;
    }


    /** Gets Announcement description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /** Gets Announcement what
     * @return string
     */
    public function getWhat()
    {
        return $this->what;
    }

    /** Gets Announcement who
     * @return string
     */
    public function getWho()
    {
        return $this->who;
    }

    /** Gets Announcement why
     * @return string
     */
    public function getWhy()
    {
        return $this->why;
    }

    /** Gets Announcement where
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Gets Announcement is_featured
     * @return int
     */
    public function getIsFeatured()
    {
        return $this->is_featured;
    }

    /**
     * Gets Facebook Post ID
     * @return string|null
     */
    public function getFacebookPostId()
    {
        return $this->facebook_post_id;
    }

    /**
     * Gets Facebook Object ID
     * @return string|null
     */
    public function getFacebookObjectId()
    {
        return $this->facebook_object_id;
    }



    // -------------------- SETTERS --------------------
    /**
     * Sets Announcement barangay_id.
     * @param $barangay_id
     * @return void
     */
    public function setBarangayId($barangay_id)
    {
        $this->barangay_id = $barangay_id;
    }

    /**
     * Sets Announcement title
     * @param $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets Announcement thumbnail_id
     * @param $thumbnail_id
     * @return void
     */
    public function setThumbnailId(?int $thumbnailId): void
    {
        $this->thumbnail_id = $thumbnailId;
    }

    /**
     * Sets Announcement description
     * @param $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Sets Announcement What Field
     * @param $what
     * @return void
     */
    public function setWhat($what)
    {
        $this->what = $what;
    }

    /**
     * Sets Announcement Why Field
     * @param $why
     * @return void
     */
    public function setWhy($why)
    {
        $this->why = $why;
    }

    /**
     * Sets Announcement Who Field
     * @param $who
     * @return void
     */
    public function setWho($who)
    {
        $this->who = $who;
    }

    /**
     * Sets Announcement Where Field
     * @param $where
     * @return void
     */
    public function setWhere($where)
    {
        $this->where = $where;
    }

    /**
     * Sets Announcement is_featured
     * @param $is_featured
     * @return void
     */
    public function setIsFeatured($is_featured)
    {
        $this->is_featured = $is_featured;
    }

    /**
     * Sets Facebook Post ID
     * @param string $facebook_post_id
     */
    public function setFacebookPostId($facebook_post_id)
    {
        $this->facebook_post_id = $facebook_post_id;
    }

    /**
    * Sets Facebook Object ID
    * @param string $facebook_object_id
    */
    public function setFacebookObjectId($facebook_object_id)
    {
        $this->facebook_object_id = $facebook_object_id;
    }





    // -------------------- CRUD OPERATIONS --------------------
    /**
     * Retrieves all Announcement records, optionally filtering by Barangay.
     *
     * @param bool $assoc
     * @param bool $assoc_basic
     * @param Barangay|null $barangay
     * @return array
     * @throws Exception
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
        $announcements = [];

        while ($row = $result->fetch_assoc()) {
            $announcement = new Announcement();
            $announcement->hydrate($row);

            if ($assoc) {
                $data = $announcement->getAssoc($assoc_basic);

                // ✅ Fetch datetimes
                require_once __DIR__ . '/AnnouncementDatetime.php';
                $datetimes = AnnouncementDatetime::getByAnnouncement($row['id'], true);
                $data['datetimes'] = array_map(function ($dt) {
                    return [
                        'id'    => $dt['id'],
                        'announcementId' => $dt['announcement_id'],
                        'date'  => $dt['date'],
                        'start' => $dt['start_time'],
                        'end'   => $dt['end_time']
                    ];
                }, $datetimes);

                // ✅ Fetch images
             
                $images = AnnouncementImage::getByAnnouncement($row['id'], true);
                $data['images'] = array_map(function ($img) {
                    return [
                        'id'    => $img['id'],
                        'announcementId' => $img['announcement_id'],
                        'name'  => $img['name']
                    ];
                }, $images);

                // ✅ Use thumbnail_id if available
                if (!empty($row['thumbnail_id'])) {
                    $thumbnail = array_filter($data['images'], function ($img) use ($row) {
                        return $img['id'] == $row['thumbnail_id'];
                    });
                    $thumbnail = reset($thumbnail);
                    $data['img'] = $thumbnail ? $thumbnail['name'] : (!empty($data['images']) ? $data['images'][0]['name'] : '');
                } else {
                    // fallback to first image
                    $data['img'] = !empty($data['images']) ? $data['images'][0]['name'] : '';
                }

                $announcements[] = $data;
            } else {
                $announcements[] = $announcement;
            }
        }

        return $announcements;
    }

    /**
     * Insert announcement
     *
     * @return bool
     * @throws Exception
     */
    public function insert(): bool
    {
        $stmt = $this->getConnection()->prepare("
            INSERT INTO `announcements` 
            (`barangay_id`, `title`, `description`, `is_featured`, `what`, `who`, `where`, `why`, `facebook_post_id`, `facebook_object_id`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->getConnection()->error);
        }

        $stmt->bind_param(
            "ississssss", 
            $this->barangay_id,
            $this->title,
            $this->description,
            $this->is_featured,
            $this->what,
            $this->who,
            $this->where,
            $this->why,
            $this->facebook_post_id,
            $this->facebook_object_id
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $this->setId($stmt->insert_id);
            return true;
        }

        return false;
    }

    /**
     * Update announcement
     *
     * @return bool
     * @throws Exception
     */
    public function update(): bool
    {
        $stmt = $this->getConnection()->prepare("
            UPDATE `" . self::$table . "` 
            SET 
                `barangay_id` = ?, 
                `title` = ?, 
                `description` = ?, 
                `is_featured` = ?, 
                `what` = ?, 
                `who` = ?, 
                `where` = ?, 
                `why` = ?, 
                `facebook_post_id` = ?,
                `facebook_object_id` = ?,
                `thumbnail_id` = ?
            WHERE `id` = ?
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->getConnection()->error);
        }

        $stmt->bind_param(
            "ississssssii",
            $this->barangay_id,
            $this->title,
            $this->description,
            $this->is_featured,
            $this->what,
            $this->who,
            $this->where,
            $this->why,
            $this->facebook_post_id,
            $this->facebook_object_id,
            $this->thumbnail_id,
            $this->id
        );

        if (!$stmt->execute()) {
            error_log("SQL Execute Error: " . $stmt->error);
            throw new Exception("Failed to execute update: " . $stmt->error);
        }

        error_log("Update executed for ID: {$this->id}, affected_rows: " . $stmt->affected_rows);

        return $stmt->affected_rows >= 0;
    }

    /**
     * Delete announcement
     *
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        // Delete associated datetimes first
        require_once __DIR__ . '/AnnouncementDatetime.php';
        
        // Delete the announcement
        $stmt = $this->getConnection()->prepare("DELETE FROM `" . self::$table . "` WHERE `id` = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }


    // -------------------- FACEBOOK CROSS PLATOFORM POSTING CRUD OPERATIONS --------------------
  
    /**
     * Create a Facebook Post based on the Announcement Object
     * Returns id and post_id of the Facebook post
     * @throws Exception
     */
    public function createFacebookPost($page_access_token, $barangayFacebookPageID)
    {
        // Convert title to uppercase and bold (using Unicode bold text)
        $boldTitle = $this->convertToBoldUnicode(mb_strtoupper($this->title));

        // Fetch all date-time entries
        $datetimes = $this->getDateTimes();

        // Build the "When" section
        $whenText = '';
        if (!empty($datetimes)) {
            foreach ($datetimes as $dt) {
                $date = date("F j, Y", strtotime($dt['date']));
                $start = date("g:ia", strtotime($dt['start_time']));
                $end   = date("g:ia", strtotime($dt['end_time']));
                $whenText .= "      " . "\u{2022} $date – $start to $end\n";
            }
        } else {
            $whenText = "To be announced\n";
        }

        // Format the Facebook Caption
        $message = <<<EOT
        $boldTitle

        "{$this->description}"




        ❓ {$this->convertToBoldUnicode("WHAT:")} {$this->what}

        📍 {$this->convertToBoldUnicode("WHERE:")} {$this->where}

        📅 {$this->convertToBoldUnicode("WHEN:")}
        $whenText

        👤 {$this->convertToBoldUnicode("WHO:")} {$this->who}

        💡 {$this->convertToBoldUnicode("WHY:")} {$this->why}

        #youthTesting
        EOT;


        // === Step 1: Get all announcement images ===
        $announcementImagesPath = __DIR__ . "/../../public/Announcements/";
        $announcementImages = AnnouncementImage::getByAnnouncement($this->getId(), true); // assuming returns array of filenames

        $uploadedPhotoIds = [];

        // === Step 2: Upload each photo as unpublished ===
        foreach ($announcementImages as $img) {
            $photoPath = $announcementImagesPath . $img['name']; 

            if (!file_exists($photoPath)) {
                continue;
            }

            $ch = curl_init();
            $data = [
                'access_token' => $page_access_token,
                'published' => 'false',
                'source' => new CURLFile($photoPath)
            ];

            curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v24.0/{$barangayFacebookPageID}/photos");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $result = json_decode($response, true);
            curl_close($ch);

            if (isset($result['id'])) {
                $uploadedPhotoIds[] = $result['id'];
            }
        }

        // === Step 3: Create a post and attach all photos ===
        $data = [
            'message' => $message,
            'access_token' => $page_access_token
        ];

        foreach ($uploadedPhotoIds as $i => $photoId) {
            $data["attached_media[$i]"] = json_encode(['media_fbid' => $photoId]);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v24.0/{$barangayFacebookPageID}/feed");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Facebook API Error: " . curl_error($ch));
        }

        curl_close($ch);

        // Decode the final Graph API response
        $result = json_decode($response, true);

        if (isset($result['id'])) {
            // The ID looks like "851109194751372_122104860351077869"
            $facebook_post_id = $result['id'];

            // Split into page_id and post_id
            [$facebook_page_id, $facebook_object_id] = explode('_', $facebook_post_id);

            // Optional: store them separately in your database
            $this->setFacebookPostId($facebook_post_id);
            $this->setFacebookObjectId($facebook_object_id);
            $this->update();

            // You can also return all of them
            return [
                'success' => true,
                'facebook_post_id' => $facebook_post_id,
                'facebook_object_id' => $facebook_object_id,
            ];
        } else {
            throw new Exception("Unexpected response from Facebook: " . json_encode($result));
        }
    }

    /**
     * Update a Facebook Post's message and attached photos based on the Announcement Object.
     * This method re-uploads all current photos and updates the post with the new photo IDs.
     * * @param string $page_access_token The access token for the page.
     * @param string $facebook_post_id The full post ID (e.g., 'page_id_post_object_id').
     * @throws Exception
     */
    public function updateFacebookPost($page_access_token, $facebook_post_id)
    {
        // --- 1. Re-generate the Message/Caption (Same as in uploadInFacebook) ---
        
        // Convert title to uppercase and bold (using Unicode bold text)
        $boldTitle = $this->convertToBoldUnicode(mb_strtoupper($this->title));

        // Fetch all date-time entries
        $datetimes = $this->getDateTimes();

        // Build the "When" section
        $whenText = '';
        if (!empty($datetimes)) {
            foreach ($datetimes as $dt) {
                $date = date("F j, Y", strtotime($dt['date']));
                $start = date("g:ia", strtotime($dt['start_time']));
                $end   = date("g:ia", strtotime($dt['end_time']));
                $whenText .= "      " . "\u{2022} $date – $start to $end\n";
            }
        } else {
            $whenText = "To be announced\n";
        }

        // Format the new Facebook Caption
        $message = <<< EOT
        $boldTitle

        "{$this->description}"
        \n


        ❓ {$this->convertToBoldUnicode("WHAT:")} {$this->what}

        📍 {$this->convertToBoldUnicode("WHERE:")} {$this->where}

        📅 {$this->convertToBoldUnicode("WHEN:")}
        $whenText

        👤 {$this->convertToBoldUnicode("WHO:")} {$this->who}

        💡 {$this->convertToBoldUnicode("WHY:")} {$this->why}

        #youthTesting
        EOT;


        // --- 2. Re-upload all photos as unpublished to get new media_fbids ---
        
        $announcementImagesPath = __DIR__ . "/../../public/Announcements/";
        $announcementImages = AnnouncementImage::getByAnnouncement($this->getId(), true); 
        $uploadedPhotoIds = [];
        $barangayFacebookPageID = explode('_', $facebook_post_id)[0]; // Extract Page ID from the full post ID

        foreach ($announcementImages as $img) {
            $photoPath = $announcementImagesPath . $img['name']; 
            
            if (!file_exists($photoPath)) {
                continue;
            }

            $ch = curl_init();
            $data = [
                'access_token' => $page_access_token,
                'published' => 'false',
                'source' => new CURLFile($photoPath)
            ];

            // Use the same page_id as the original post for the upload
            curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v24.0/{$barangayFacebookPageID}/photos");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $result = json_decode($response, true);
            curl_close($ch);

            if (isset($result['id'])) {
                $uploadedPhotoIds[] = $result['id'];
            }
        }
        
        // --- 3. Update the existing post with the new message and attached media ---
        
        $data = [
            'message' => $message,
            'access_token' => $page_access_token
        ];

        // Attach the newly uploaded, unpublished media_fbids
        foreach ($uploadedPhotoIds as $i => $photoId) {
            $data["attached_media[$i]"] = json_encode(['media_fbid' => $photoId]);
        }
        
        // The endpoint for updating an existing post is the post ID itself (using POST method)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v24.0/{$facebook_post_id}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Facebook API Error on Update: " . curl_error($ch));
        }

        curl_close($ch);

        // Decode the final Graph API response
        $result = json_decode($response, true);

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'success' => true,
                'facebook_post_id' => $facebook_post_id,
            ];
        } else {
            throw new Exception("Unexpected response from Facebook during update: " . json_encode($result));
        }
    }

    /**
     * Delete a Facebook Post based of the Announcement Object
     * @return mixed
     * @throws Exception
     */
    function deleteFacebookPost($postId, $pageAccessToken) {
        $graphUrl = "https://graph.facebook.com/v24.0/{$postId}?access_token={$pageAccessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $graphUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            returnError("Facebook API Error: " . curl_error($ch), 500);
            echo json_encode(["error" => curl_error($ch)]);
        } else {
            return json_decode($response, true); // Returns {"success": true} if deleted
        }
        
        curl_close($ch);
    }





    // -------------------- UTILITY FUNCTIONS --------------------
    /**
     * Returns the count of announcements made in a given year for a specific barangay.
     *
     * @param string $barangaySlug
     * @param int $year
     * @return int
     * @throws Exception
     */
    public static function getAnnualCount(string $barangaySlug, int $year): int
    {
        $conn = self::getConnectionStatic();
        $query = "SELECT COUNT(*) AS count
                  FROM `" . self::$table . "` a
                  JOIN barangays b ON a.barangay_id = b.id
                  WHERE YEAR(a.created_at) = ? AND b.slug = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("is", $year, $barangaySlug);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }

    /**
     * Returns a summary of announcements per month and total announcements per year.
     * If a barangay slug is provided, the summary is generated only for that specific barangay.
     *
     * @param string|null $barangaySlug
     * @return array
     * @throws Exception
     */
    
    public static function getMonthlySummary(?string $barangaySlug = null): array
    {
        $conn = self::getConnectionStatic();

        // Monthly summary using the "date" column
        if ($barangaySlug !== null) {
            $queryMonthly = "SELECT YEAR(a.date) AS year, MONTHNAME(a.date) AS month, COUNT(*) AS count
                             FROM `" . self::$table . "` a
                             JOIN barangays b ON a.barangay_id = b.id
                             WHERE b.slug = ?
                             GROUP BY YEAR(a.date), MONTH(a.date)
                             ORDER BY YEAR(a.date) ASC, MONTH(a.date) ASC";
            $stmt = $conn->prepare($queryMonthly);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $barangaySlug);
        } else {
            $queryMonthly = "SELECT YEAR(date) AS year, MONTHNAME(date) AS month, COUNT(*) AS count
                             FROM `" . self::$table . "`
                             GROUP BY YEAR(date), MONTH(date)
                             ORDER BY YEAR(date) ASC, MONTH(date) ASC";
            $stmt = $conn->prepare($queryMonthly);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $monthlyGrouped = [];
        while ($row = $result->fetch_assoc()) {
            $year = $row['year'];
            if (!isset($monthlyGrouped[$year])) {
                $monthlyGrouped[$year] = [];
            }
            $monthlyGrouped[$year][] = [
                'month' => $row['month'],
                'count' => $row['count'],
            ];
        }

        // Annual summary using the "date" column
        if ($barangaySlug !== null) {
            $queryAnnual = "SELECT YEAR(a.date) AS year, COUNT(*) AS total
                            FROM `" . self::$table . "` a
                            JOIN barangays b ON a.barangay_id = b.id
                            WHERE b.slug = ?
                            GROUP BY YEAR(a.date)
                            ORDER BY year ASC";
            $stmt = $conn->prepare($queryAnnual);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $barangaySlug);
        } else {
            $queryAnnual = "SELECT YEAR(date) AS year, COUNT(*) AS total
                            FROM `" . self::$table . "`
                            GROUP BY YEAR(date)
                            ORDER BY year ASC";
            $stmt = $conn->prepare($queryAnnual);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $annual = [];
        while ($row = $result->fetch_assoc()) {
            $annual[] = $row;
        }

        return [
            'monthly' => $monthlyGrouped,
            'annual'  => $annual
        ];
    }

    /**
     * Gets the Barangay that this Announcement belongs to.
     *
     * @param bool $assoc
     * @param bool $assoc_basic
     * @return Barangay|array|null
     * @throws Exception
     */
    public function getBarangay(bool $assoc = false, bool $assoc_basic = false): Barangay|array|null
    {
        require_once __DIR__ . '/Barangay.php';
        $barangay = Barangay::find($this->barangay_id);
        return ($assoc && $barangay) ? $barangay->getAssoc($assoc_basic) : $barangay;
    }

    /**
     * Get announcements by month-year string (e.g. "August 2025")
     *
     * @param string $monthYear
     * @param Barangay|null $barangay
     * @param bool $assoc
     * @param bool $assoc_basic
     * @return array
     * @throws Exception
     */
    public static function getByMonthYear(string $monthYear, ?Barangay $barangay = null, bool $assoc = true, bool $assoc_basic = false): array
    {
        $conn = self::getConnectionStatic();

        // Convert "August 2025" to "YYYY-MM"
        $timestamp = strtotime($monthYear);
        if (!$timestamp) {
            throw new Exception("Invalid month-year format: $monthYear");
        }
        $yearMonth = date('Y-m', $timestamp); // e.g., "2025-08"

        // Query: join announcement_datetime to announcement
        $query = "
            SELECT DISTINCT a.*
            FROM `" . Announcement::$table . "` a
            INNER JOIN `" . AnnouncementDatetime::$table . "` ad
                ON a.id = ad.announcement_id
            WHERE ad.date LIKE ?
        ";


        $params = ["$yearMonth%"];
        $types = "s";

        if ($barangay !== null) {
            $query .= " AND a.barangay_id = ?";
            $params[] = $barangay->getId();
            $types .= "i";
        }

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $announcement = new Announcement();
            $announcement->hydrate($row);

            if ($assoc) {
                $data = $announcement->getAssoc($assoc_basic);

                // ✅ Fetch datetimes
                require_once __DIR__ . '/AnnouncementDatetime.php';
                $datetimes = AnnouncementDatetime::getByAnnouncement($row['id'], true);
                $data['datetimes'] = array_map(function ($dt) {
                    return [
                        'id'             => $dt['id'],
                        'announcementId' => $dt['announcement_id'],
                        'date'           => $dt['date'],
                        'start'          => $dt['start_time'],
                        'end'            => $dt['end_time']
                    ];
                }, $datetimes);

                // ✅ Fetch images
                require_once __DIR__ . '/AnnouncementImage.php';
                $images = AnnouncementImage::getByAnnouncement($row['id'], true);
                $data['images'] = array_map(function ($img) {
                    return [
                        'id'             => $img['id'],
                        'announcementId' => $img['announcement_id'],
                        'name'           => $img['name']
                    ];
                }, $images);

                // ✅ Use thumbnail_id if available
                if (!empty($row['thumbnail_id'])) {
                    $thumbnail = array_filter($data['images'], function ($img) use ($row) {
                        return $img['id'] == $row['thumbnail_id'];
                    });
                    $thumbnail = reset($thumbnail);
                    $data['img'] = $thumbnail
                        ? $thumbnail['name']
                        : (!empty($data['images']) ? $data['images'][0]['name'] : '');
                } else {
                    // fallback to first image
                    $data['img'] = !empty($data['images']) ? $data['images'][0]['name'] : '';
                }

                $announcements[] = $data;
            } else {
                $announcements[] = $announcement;
            }
        }

        return $announcements;
    }

    /**
     * Get featured announcements
     *
     * @param bool $assoc
     * @param bool $assoc_basic
     * @param Barangay|null $barangay
     * @return array
     * @throws Exception
     */
    public static function getFeatured(bool $assoc = false, bool $assoc_basic = false, ?Barangay $barangay = null): array
    {
        $conn = self::getConnectionStatic();

        $query = "SELECT * FROM `" . self::$table . "` WHERE is_featured = 1";
        $params = [];
        $types = "";

        if ($barangay !== null) {
            $query .= " AND barangay_id = ?";
            $params[] = $barangay->getId();
            $types .= "i";
        }

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $announcement = new Announcement();
            $announcement->hydrate($row);

            if ($assoc) {
                $data = $announcement->getAssoc($assoc_basic);

                // ✅ Fetch datetimes
                require_once __DIR__ . '/AnnouncementDatetime.php';
                $datetimes = AnnouncementDatetime::getByAnnouncement($row['id'], true);
                $data['datetimes'] = array_map(function ($dt) {
                    return [
                        'id'            => $dt['id'],
                        'announcementId'=> $dt['announcement_id'],
                        'date'          => $dt['date'],
                        'start'         => $dt['start_time'],
                        'end'           => $dt['end_time']
                    ];
                }, $datetimes);

                // ✅ Fetch images
                require_once __DIR__ . '/AnnouncementImage.php';
                $images = AnnouncementImage::getByAnnouncement($row['id'], true);
                $data['images'] = array_map(function ($img) {
                    return [
                        'id'            => $img['id'],
                        'announcementId'=> $img['announcement_id'],
                        'name'          => $img['name']
                    ];
                }, $images);

                // ✅ Use thumbnail_id if available
                if (!empty($row['thumbnail_id'])) {
                    $thumbnail = array_filter($data['images'], function ($img) use ($row) {
                        return $img['id'] == $row['thumbnail_id'];
                    });
                    $thumbnail = reset($thumbnail);
                    $data['img'] = $thumbnail
                        ? $thumbnail['name']
                        : (!empty($data['images']) ? $data['images'][0]['name'] : '');
                } else {
                    // fallback to first image
                    $data['img'] = !empty($data['images']) ? $data['images'][0]['name'] : '';
                }

                $announcements[] = $data;
            } else {
                $announcements[] = $announcement;
            }
        }

        return $announcements;
    }

    public function getDateTimes() {
        $datetime = AnnouncementDatetime::getByAnnouncement($this->getId(), true);
        return $datetime;
    }







    // -------------------- HELPER FUNCTIONS --------------------

    /**
     * Update the announcement along with its associated datetimes (all-in-one method)
     * @param array $datetimes Optional array of datetime data
     * @return bool
     * @throws Exception
     */
    public function updateWithDatetimes(array $datetimes = []): bool
    {
        require_once __DIR__ . '/AnnouncementDatetime.php';

        error_log("updateWithDatetimes called for ID: " . $this->getId());

        // --- Step 1: Update the main announcement ---
        $updateResult = $this->update();
        error_log("Main update result: " . ($updateResult ? 'true' : 'false'));

        if (!$updateResult) {
            error_log("Main announcement update failed");
            return false;
        }

        // --- Step 2: Handle datetimes if provided ---
        if (isset($datetimes)) {
            try {
                $announcementId = $this->getId();

                // If datetimes array is empty → delete all
                if (empty($datetimes)) {
                    AnnouncementDatetime::deleteByAnnouncement($announcementId);
                    error_log("All datetimes deleted for announcement {$announcementId} (empty array case)");
                    return true;
                }

                // Build existing map
                $existingDatetimes = AnnouncementDatetime::getByAnnouncement($announcementId);
                $existingMap = [];
                foreach ($existingDatetimes as $dt) {
                    $existingMap[$dt->getId()] = $dt;
                }

                $usedIds = [];

                // --- Process provided datetimes ---
                foreach ($datetimes as $dt) {
                    if (empty($dt['date'])) {
                        error_log("Skipping datetime with empty date for announcement {$announcementId}");
                        continue;
                    }

                    $start = $this->normalizeTime($dt['start'] ?? '');
                    $end = $this->normalizeTime($dt['end'] ?? '');

                    if (!empty($dt['id']) && isset($existingMap[$dt['id']])) {
                        // Update existing
                        $adt = new AnnouncementDatetime($dt['id']);
                        $adt->setDate($dt['date']);
                        $adt->setStartTime($start);
                        $adt->setEndTime($end);

                        if ($adt->update()) {
                            $usedIds[] = $dt['id'];
                            error_log("Updated datetime ID {$dt['id']} for announcement {$announcementId}");
                        } else {
                            error_log("Failed to update datetime ID {$dt['id']} for announcement {$announcementId}");
                        }
                    } else {
                        // Insert new
                        $adt = new AnnouncementDatetime();
                        $adt->setAnnouncementId($announcementId);
                        $adt->setDate($dt['date']);
                        $adt->setStartTime($start);
                        $adt->setEndTime($end);

                        if ($adt->insert()) {
                            error_log("Inserted new datetime for announcement {$announcementId} ({$dt['date']} $start-$end)");
                        } else {
                            error_log("Failed to insert new datetime for announcement {$announcementId}");
                        }
                    }
                }

                // --- Delete unused datetimes ---
                foreach ($existingMap as $id => $dt) {
                    if (!in_array($id, $usedIds)) {
                        $delDt = new AnnouncementDatetime($id);
                        if ($delDt->delete()) {
                            error_log("Deleted datetime ID $id for announcement {$announcementId}");
                        } else {
                            error_log("Failed to delete datetime ID $id for announcement {$announcementId}");
                        }
                    }
                }

                error_log("Datetimes updated successfully for announcement {$announcementId}");

            } catch (Exception $e) {
                error_log("Datetime update failed: " . $e->getMessage());
                // Optional: throw $e; // Uncomment if you want the entire operation to fail
            }
        }

        return true;
    }

    /**
     * Normalize time format to HH:mm:ss
     *
     * @param string $time
     * @return string
     */
    private function normalizeTime(string $time): string
    {
        if (empty($time)) {
            return '';
        }

        // If already in HH:mm:ss format, return as is
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // If in HH:mm format, add :00
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // If in H:mm format, pad hour
        if (preg_match('/^\d{1}:\d{2}$/', $time)) {
            return '0' . $time;
        }

        // If in H:mm:ss format, pad hour
        if (preg_match('/^\d{1}:\d{2}:\d{2}$/', $time)) {
            return '0' . $time;
        }

        return $time;
    }

    public function updateThumbnail(): bool
    {
        $stmt = $this->getConnection()->prepare("
            UPDATE `announcements` 
            SET `thumbnail_id` = ? 
            WHERE `id` = ?
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->getConnection()->error);
        }

        $stmt->bind_param("ii", $this->thumbnail_id, $this->id);

        return $stmt->execute();
    }
    private function convertToBoldUnicode($text) {
        $normal = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $bold   = '𝗔𝗕𝗖𝗗𝗘𝗙𝗚𝗛𝗜𝗝𝗞𝗟𝗠𝗡𝗢𝗣𝗤𝗥𝗦𝗧𝗨𝗩𝗪𝗫𝗬𝗭𝗮𝗯𝗰𝗱𝗲𝗳𝗴𝗵𝗶𝗷𝗸𝗹𝗺𝗻𝗼𝗽𝗾𝗿𝘀𝘁𝘂𝘃𝘄𝘅𝘆𝘇';
        return strtr($text, array_combine(preg_split('//u', $normal, -1, PREG_SPLIT_NO_EMPTY), preg_split('//u', $bold, -1, PREG_SPLIT_NO_EMPTY)));
    }
}
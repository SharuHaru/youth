<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/AchievementImage.php';
require_once __DIR__ . '/AchievementDate.php';
require_once __DIR__ . '/BarangayFacebookPages.php';
require_once __DIR__ . '/FacebookPageTokens.php';

class Achievement extends Model
{
    /** static data */
    public    static $table         = 'achievements';
    public    static $table_columns = [];
    protected static $basic_columns = ['id', 'title', 'subtitle','info', 'date'];

    /** properties */
    protected $sk_official_id       = 0;
    protected $sk_official_name     = '';
    protected $title                = '';
    protected $subtitle             = '';
    protected $info                 = '';
    protected ?int $thumbnail_id = null;
    protected $sk_official_comment  = '';
    protected $sk_official_img      = '';
    protected $sk_official_position  = '';
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
     * Gets Achievement sk_official_id.
     * @return int
     */
    public function getSkOfficialId()
    {
        return $this->sk_official_id;
    }

    /** Gets Achievement sk_official_name
     * @return string
     */
    public function getSkOfficialName()
    {
        return $this->sk_official_name;
    }

    /**
     * Gets Achievement title.
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets Achievement subtitle.
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Gets Achievement info.
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Gets Achievement thumbnailId.
     * @return int
     */
    public function getThumbnailId()
    {
        return $this->thumbnail_id;
    }

    /**
     * Gets Achievement skOfficialComment.
     * @return string
     */
    public function getSkOfficialComment()
    {
        return $this->sk_official_comment;
    }

    /**
     * Gets SK Official's image.
     * @return string
     */
    public function getSkOfficialImg()
    {
        return $this->sk_official_img;
    }

    /**
     * Gets SK Official's position.
     * @return string
     */
    public function getSkOfficialPosition()
    {
        return $this->sk_official_position;
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
     * Sets Achievement sk_official_id.
     * @param $sk_official_id
     * @return void
     */
    public function setSkOfficialId($sk_official_id)
    {
        $this->sk_official_id = $sk_official_id;
    }

    /**
     * Sets Achievement sk_official_name.
     * @param $sk_official_name
     * @return void
     */
    public function setSkOfficialName($sk_official_name)
    {
        $this->sk_official_name = $sk_official_name;
    }

    /**
     * Sets Achievement title.
     * @param $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets Achievement subtitle.
     * @param $subtitle
     * @return void
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Sets Achievement info.
     * @param $info
     * @return void
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * Sets Achievement thumbnail_id.
     * @param $thumbnail_id
     * @return void
     */
    public function setThumbnailId($thumbnail_id)
    {
        $this->thumbnail_id = $thumbnail_id;
    }

    /**
     * Sets Achievement sk_official_comment.
     * @param $sk_official_comment
     * @return void
     */
    public function setSkOfficialComment($sk_official_comment)
    {
        $this->sk_official_comment = $sk_official_comment;
    }

    /**
     * Sets SK Official's image.
     *
     * @param $img
     * @return void
     */
    public function setSkOfficialImg($img)
    {
        $this->sk_official_img = $img;
    }

    /**
     * Sets SK Official's position.
     *
     * @param $position
     * @return void
     */
    public function setSkOfficialPosition($position)
    {
        $this->sk_official_position = $position;
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
     * Override getAssoc() to include sk_official_name.
     *
     * @param bool $basic
     * @return array
     */
    public function getAssoc(bool $basic = false): array
    {
        // first, call the parent method to get the base associative array
        $arr = parent::getAssoc($basic);

        // then, add this field to include sk_official_name
        $arr['sk_official_name'] = $this->getSkOfficialName();
        $arr['sk_official_img'] = $this->getSkOfficialImg();
        $arr['sk_official_position'] = $this->getSkOfficialPosition();
        return $arr;
    }

    /**
     * Retrieves all Achievement records, optionally filtering by SK Official,
     * and includes related achievement_date records.
     *
     * @param bool $assoc
     * @param bool $assoc_basic
     * @param SkOfficial|null $skOfficial
     * @return array
     */
    public static function all(bool $assoc = false, bool $assoc_basic = false, ?SkOfficial $skOfficial = null): array
    {
        // Base query: achievements + SK official details
        $query = "SELECT a.*,
                        s.full_name AS sk_official_name,
                        s.img AS sk_official_img,
                        s.position AS sk_official_position
                FROM `" . self::$table . "` a
                JOIN sk_officials s ON a.sk_official_id = s.id";
        $params = [];
        $types = "";

        if ($skOfficial !== null) {
            $query .= " WHERE a.sk_official_id = ?";
            $params[] = $skOfficial->getId();
            $types = "i";
        }

        $stmt = self::getConnectionStatic()->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $achievements = [];

        while ($row = $result->fetch_assoc()) {
            $achievement = new Achievement();
            $row['sk_official_name'] = $row['sk_official_name'] ?? '';
            $achievement->setSkOfficialImg($row['sk_official_img'] ?? '');
            $achievement->setSkOfficialPosition($row['sk_official_position'] ?? '');
            $achievement->hydrate($row);

            if ($assoc) {
                $data = $achievement->getAssoc($assoc_basic);
                

                // ✅ Fetch achievement dates
                require_once __DIR__ . '/AchievementDate.php';
                $dates = AchievementDate::getByAchievement($row['id'], true);
                $data['dates'] = array_map(function ($dt) {
                    return [
                        'id'             => $dt['id'],
                        'achievementId'  => $dt['achievement_id'],
                        'date'           => $dt['date'],
                    ];
                }, $dates);

                // Fetch Images
                $images = AchievementImage::getByAchievement($row['id'], true);
                $data['images'] = array_map(function ($img) {
                    return [
                        'id'    => $img['id'],
                        'achievementId' => $img['achievement_id'],
                        'img'  => $img['img']
                    ];
                }, $images);

                // ✅ Use thumbnail_id if available
                if (!empty($row['thumbnail_id'])) {
                    $thumbnail = array_filter($data['images'], function ($img) use ($row) {
                        return $img['id'] == $row['thumbnail_id'];
                    });
                    $thumbnail = reset($thumbnail);
                    $data['img'] = $thumbnail ? $thumbnail['img'] : (!empty($data['images']) ? $data['images'][0]['img'] : '');
                } else {
                    // fallback to first image
                    $data['img'] = !empty($data['images']) ? $data['images'][0]['img'] : '';
                }

                $achievements[] = $data;
            } else {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Insert achievement
     *
     * @return bool
     * @throws Exception
     */
    public function insert(): bool
    {
        $stmt = $this->getConnection()->prepare("
            INSERT INTO `" . self::$table . "` 
            (`sk_official_id`, `title`, `subtitle`, `info`, `thumbnail_id`, `sk_official_comment`, `facebook_post_id`, `facebook_object_id`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssisss", 
            $this->sk_official_id, 
            $this->title, 
            $this->subtitle, 
            $this->info, 
            $this->thumbnail_id, 
            $this->sk_official_comment,
            $this->facebook_post_id,
            $this->facebook_object_id
        );

        if ($stmt->execute()) {
            $this->setId($stmt->insert_id);
            return true;
        }
        return false;
    }

    /**
     * Update achievement
     *
     * @return bool
     * @throws Exception
     */
    public function update(): bool
    {
        $stmt = $this->getConnection()->prepare("
            UPDATE `" . self::$table . "` 
            SET 
                `sk_official_id` = ?, 
                `title` = ?, `subtitle` = ?, 
                `info` = ?, 
                `thumbnail_id` = ?,  
                `sk_official_comment` = ?, 
                `facebook_post_id` = ?,
                `facebook_object_id` = ? 
            WHERE `id` = ?
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->getConnection()->error);
        }

        $stmt->bind_param(
            "isssisssi", 
            $this->sk_official_id, 
            $this->title, 
            $this->subtitle, 
            $this->info, 
            $this->thumbnail_id, 
            $this->sk_official_comment, 
            $this->facebook_post_id,
            $this->facebook_object_id,
            $this->id
        );
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Delete achievement
     *
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        $stmt = $this->getConnection()->prepare("DELETE FROM `" . self::$table . "` WHERE `id` = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }


    // -------------------- FACEBOOK CROSS PLATOFORM POSTING CRUD OPERATIONS --------------------

    public function createFacebookPost($page_access_token, $barangayFacebookPageID) {

        // ------------- STEP 1: Prepare the Caption for the Facebook Post -------------

        // 1.2 Fetch the Achievement DateTimes
        $achievementDates = $this->getDatetimes();

        // Build the Date Section
        $whenText = '';
        if (!empty($achievementDates)) {
            foreach ($achievementDates as $d) {
                $date = date("F j, Y", strtotime($d['date']));
                $whenText .= "      " . "\u{2022} $date \n";
            }
        }

        $caption =  <<<EOT
        $this->title

        "$this->subtitle"

        $this->info


        📅 Date Accomplished:
        $whenText
        


        {$this->getSkOfficial(true, true)['full_name']}
                $this->sk_official_comment
        EOT;

        // ------------- STEP 2: Upload each photo as unpublished -------------
        $achievementImagesPath = __DIR__ . "/../../public/Achievements/";
        $achievementImages = AchievementImage::getByAchievement($this->getId());
        $uploadedPhotoIds = [];

        foreach ($achievementImages as $img) {
            $imagePath = $achievementImagesPath . $img->getImg();
            
            if (!file_exists($imagePath)) {
                continue;
            }

            $ch = curl_init();
            $data = [
                'access_token' => $page_access_token,
                'published' => 'false',
                'source' => new CURLFile($imagePath)
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

        // ------------- STEP 3: Create an Achievement Post and attach all photos -------------
        $data = [
            'message' => $caption,
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

        // ------------- STEP 4: Update the Achievement Database with postid and object id  -------------

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

            // ------------- STEP 5: Return  -------------
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
     * Update a Facebook Post based of the Achievement Object
     * @return mixed
     * @throws Exception
     */
    function updateFacebookPost($page_access_token, $facebook_post_id) {

        // ------------- STEP 1: Re-generate the Message/Caption (Same as in uploadInFacebook) -------------

         // 1.2 Fetch the Achievement DateTimes
        $achievementDates = $this->getDatetimes();

        // Build the Date Section
        $whenText = '';
        if (!empty($achievementDates)) {
            foreach ($achievementDates as $d) {
                $date = date("F j, Y", strtotime($d['date']));
                $whenText .= "      " . "\u{2022} $date \n";
            }
        }

        $caption =  <<<EOT
        $this->title

        "$this->subtitle"

        $this->info


        📅 Date Accomplished:
        $whenText
        


        {$this->getSkOfficial(true, true)['full_name']}
                $this->sk_official_comment
        EOT;

        // ------------- STEP 2: Re-upload all photos as unpublished to get new media_fbids -------------
        $achievementImagesPath = __DIR__ . "/../../public/Achievements/";
        $achievementImages = AchievementImage::getByAchievement($this->getId());
        $uploadedPhotoIds = [];
        $barangayFacebookPageID = explode('_', $facebook_post_id)[0]; // Extract Page ID from the full post ID

        foreach ($achievementImages as $img) {
            $photoPath = $achievementImagesPath . $img->getImg(); 
            
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

        // ------------- STEP 3: Update the existing post with the new message and attached media -------------

        $data = [
            'message' => $caption,
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

        // ------------- STEP 4: RETURN -------------
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
     * Delete a Facebook Post based of the Achievement Object
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
            echo json_encode(["error" => curl_error($ch)]);
            curl_close($ch);
            returnError("Facebook API Error: " . curl_error($ch), 500);
        } else {
            return json_decode($response, true); // Returns {"success": true} if deleted
        }
        
        curl_close($ch);
    }




    // -------------------- UTILITY FUNCTIONS --------------------

    /**
    * Returns a summary of achievements per month and total achievements per year.
    * If a barangay slug is provided, the summary is generated only for that specific barangay.
    * @param string|null $barangaySlug
    * @return array
    * @throws Exception
    */
    public static function getMonthlySummary(?string $barangaySlug = null): array
    {
        $conn = self::getConnectionStatic();

        if ($barangaySlug !== null) {
            // when filtering by barangay, join with the barangays table and filter by slug
            $queryMonthly = "SELECT YEAR(a.date) AS year, MONTHNAME(a.date) AS month, COUNT(*) AS count
                             FROM `" . self::$table . "` a
                             JOIN barangays b ON a.sk_official_id IN (
                                 SELECT id FROM sk_officials WHERE barangay_id = b.id
                             )
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

        // Annual summary
        if ($barangaySlug !== null) {
            $queryAnnual = "SELECT YEAR(a.date) AS year, COUNT(*) AS total
                            FROM `" . self::$table . "` a
                            JOIN sk_officials s ON a.sk_official_id = s.id
                            JOIN barangays b ON s.barangay_id = b.id
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
     * Gets the SK Official that this Achievement belongs to.
     *
     * @param bool $assoc
     * @param bool $assoc_basic
     * @return SkOfficial|array|null
     * @throws Exception
     */
    public function getSkOfficial(bool $assoc = false, bool $assoc_basic = false): array|SkOfficial|null
    {
        require_once __DIR__ . '/SkOfficial.php';
        $skOfficial = SkOfficial::find($this->sk_official_id);
        return ($assoc && $skOfficial) ? $skOfficial->getAssoc($assoc_basic) : $skOfficial;
    }



    // -------------------- HELPER FUNCTIONS --------------------

    /**
     * Update achievement dates
     *
     * @param array $dates
     * @return bool
     * @throws Exception
     */
    public function updateDates(array $dates): bool
    {
        require_once __DIR__ . '/AchievementDate.php';

        $achievementId = $this->getId();
        if (!$achievementId) {
            throw new Exception("Cannot update dates: Achievement ID not set.");
        }

        // If no dates provided, delete all
        if (empty($dates)) {
            $existingDates = AchievementDate::getByAchievement($achievementId);
            foreach ($existingDates as $dt) {
                $delDt = new AchievementDate($dt->getId());
                if ($delDt->delete()) {
                    error_log("✅ Deleted date ID {$dt->getId()} for achievement {$achievementId} (empty array case)");
                } else {
                    error_log("❌ Failed to delete date ID {$dt->getId()} for achievement {$achievementId}");
                }
            }
            return true;
        }

        // --- Sync logic ---
        $existingDates = AchievementDate::getByAchievement($achievementId);
        $existingMap   = [];
        foreach ($existingDates as $dt) {
            $existingMap[$dt->getId()] = $dt;
        }

        $usedIds = [];

        foreach ($dates as $dt) {
            if (empty($dt['date'])) {
                error_log("⚠️ Skipping date with empty value for achievement {$achievementId}");
                continue;
            }

            if (!empty($dt['id']) && isset($existingMap[$dt['id']])) {
                // Update existing
                $ad = new AchievementDate($dt['id']);
                $ad->setAchievementId($achievementId);
                $ad->setDate($dt['date']);

                if ($ad->update()) {
                    $usedIds[] = $dt['id'];
                    error_log("✅ Updated date ID {$dt['id']} for achievement {$achievementId}");
                } else {
                    error_log("❌ Failed to update date ID {$dt['id']} for achievement {$achievementId}");
                }
            } else {
                // Insert new
                $ad = new AchievementDate();
                $ad->setAchievementId($achievementId);
                $ad->setDate($dt['date']);

                if ($ad->insert()) {
                    $newId = $ad->getId(); // ensure your AchievementDate::insert() sets the ID
                    if ($newId) {
                        $usedIds[] = $newId;
                    }
                    error_log("✅ Inserted new date for achievement {$achievementId} ({$dt['date']})");
                } else {
                    error_log("❌ Failed to insert new date for achievement {$achievementId}");
                }
            }
        }

        // Delete dates not in the new list
        foreach ($existingMap as $id => $dt) {
            if (!in_array($id, $usedIds)) {
                $delDt = new AchievementDate($id);
                if ($delDt->delete()) {
                    error_log("🗑️ Deleted date ID $id for achievement {$achievementId}");
                } else {
                    error_log("❌ Failed to delete date ID $id for achievement {$achievementId}");
                }
            }
        }

        return true;
    }

    public function updateThumbnail(): bool
    {
        $stmt = $this->getConnection()->prepare("
            UPDATE `achievements` 
            SET `thumbnail_id` = ? 
            WHERE `id` = ?
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->getConnection()->error);
        }

        $stmt->bind_param("ii", $this->thumbnail_id, $this->id);

        return $stmt->execute();
    }

    public function getDatetimes(): array
    {
        require_once __DIR__ . '/AchievementDate.php';
        return AchievementDate::getByAchievement($this->getId(), true);
    }
}

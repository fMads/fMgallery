<?php
class fMgallery {
	private $db;
	
	private $category;
	private $album;
	private $picture;
	
	public function __construct($db) {
		$this->db = $db;
	}
	
	public function categories_get($category_id = null) {
		return $this->db->pull("
			SELECT		gc.id,
						gc.name,
						gc.file,
						gc.file_version
			FROM		fM_gallery_category AS gc
			" . (($category_id != null) ? "WHERE		gc.id = '" . $this->db->escape_string($category_id) . "'" : "") . "
			ORDER BY	gc.name	ASC
		");
	}
	
	public function albums_get($category_id = null, $album_id = null) {
		if($category_id != null) $where["ga.category_id"] = $category_id;
		if($album_id != null) $where["ga.id"] = $album_id;
		if(isset($where)) {
			foreach($where AS $i => $t) {
				if(isset($w)) $w .= " || ";
				$w .= $i . " = '" . $this->db->escape_string($t) . "'";
			}
			$w = "WHERE		(" . $w . ")";
		}
		
		$albums = $this->db->pull("
			SELECT		ga.id,
						ga.name,
						ga.category_id
			FROM		fM_gallery_album AS ga
			" . $w . "
			ORDER BY	ga.time	DESC
		");
		if($albums[0] != "") {
			foreach($albums AS $i => $album) {
				$file = $this->db->pull("
					SELECT		gp.file,
								gp.file_version
					FROM		fM_gallery_picture AS gp
					WHERE		gp.album_id = '" . $this->db->escape_string($album["id"]) . "'
					ORDER BY	gp.file	ASC
					LIMIT		1
				");
				$albums[$i]["file"] = $file[0]["file"];
				$albums[$i]["file_version"] = $file[0]["file_version"];
			}
		}
		
		return $albums;
	}
	
	public function album_start($album_id) {
		return $this->db->pull("
			SELECT		gp.id,
						gp.file,
						gp.file_version
			FROM		fM_gallery_picture AS gp
			WHERE		gp.album_id = '" . $this->db->escape_string($album_id) . "'
			ORDER BY	gp.file	ASC
		");
	}
	
	public function pictures_get($picture_id = null) {
		return $this->db->pull("
			SELECT		*
			FROM		fM_gallery_picture AS gp
			" . (($picture_id != null) ? "WHERE		gp.id = '" . $this->db->escape_string($picture_id) . "'" : "") . "
			ORDER BY	gp.file	ASC
		");
	}
	
	public function pictures_last_get($limit = 20) {
		return $this->db->pull("
			SELECT		*
			FROM		fM_gallery_picture AS gp
			ORDER BY	gp.file	ASC
			LIMIT		" . $this->db->escape_string($limit) . "
		");
	}
	
	public function album_start_quick($album_id) {
		$this->album_this($album_id);
		
		return array(
			"albums"		=> $this->albums_get($this->album["category_id"]),
			"album_start"	=> $this->album_start($this->album["id"]),
			"album_id"		=> $this->album["id"]
		);
	}
	
	public function category_this($id) {
		$category = $this->categories_get($id);
		$this->category = $category[0];
	}
	
	public function album_this($id) {
		$album = $this->albums_get(null,$id);
		$this->album = $album[0];
	}
	
	public function picture_this($id) {
		$picture = $this->pictures_get($id);
		$this->picture = $picture[0];
	}
	
	
	public function category_get($type = null) {
		if($type == null) {
			return $this->category;
		} else {
			return $this->category[$type];
		}
	}
	
	public function album_get($type = null) {
		if($type == null) {
			return $this->album;
		} else {
			return $this->album[$type];
		}
	}
	
	public function picture_get($type = null) {
		if($type == null) {
			return $this->picture;
		} else {
			return $this->picture[$type];
		}
	}
	
}
class fMgallery_admin extends fMgallery{
	private $db;
	
	private $category;
	private $album;
	private $picture;
	
	private $user;
	
	public function __construct($db) {
		parent::__construct($db);
		$this->db = $db;
	}
	
//	USER
	public function user_this($user) {
		$this->user = $user;
	}
	
//	CATEGORY
	public function category_add($name,$file) {
		$this->db->query("
			INSERT INTO `fM_gallery_category`
			SET			`name` = '" . $this->db->escape_string(trim(utf8_decode($name))) . "',
						`file` = '" . $this->db->escape_string(trim(utf8_decode($file))) . "'
		");
		
		$this->category_this(mysql_insert_id());
	}
	
	public function category_update($name,$value) {
		$this->db->query("
			UPDATE	`fM_gallery_category`
			SET		`" . $name . "` = '" . $this->db->escape_string($value) . "'
			WHERE	`id` = '" . $this->category["id"] . "'
		");
		
		$this->category_this($this->category["id"]);
	}
	
	public function category_file_version_next() {
		$this->category_update("file_version",$this->category["file_version"] + 1);
	}

//	ALBUM
	public function album_add($name) {
		$this->db->query("
			INSERT INTO `fM_gallery_album`
			SET			`category_id` = '" . $this->category["id"] . "',
						`name` = '" . $this->db->escape_string($name) . "'
		");
		
		$this->album_this(mysql_insert_id());
		
		mkdir("./images/gallery/" . $this->album_get("id"));
		mkdir("./images/gallery/" . $this->album_get("id") . "/medium");
		mkdir("./images/gallery/" . $this->album_get("id") . "/thumbnail");
	}
	
	public function album_update($name,$value) {
		$this->db->query("
			UPDATE	`fM_gallery_album`
			SET		`" . $name . "` = '" . $this->db->escape_string($value) . "'
			WHERE	`id` = '" . $this->album["id"] . "'
		");
		
		$this->album_this($this->album["id"]);
	}

//	PICTURE
	public function picture_add($file) {
		$this->db->query("
			INSERT INTO `fM_gallery_picture`
			SET			`album_id` = '" . $this->album["id"] . "',
						`file` = '" . $this->db->escape_string($file) . "',
						`user_id` = '" . $this->user["id"] . "',
						`time` = '" . time() . "'
		");
		
		$this->picture_this(mysql_insert_id());
	}
	
	public function picture_update($name,$value) {
		$this->db->query("
			UPDATE	`fM_gallery_picture`
			SET		`" . $name . "` = '" . $this->db->escape_string($value) . "'
			WHERE	`id` = '" . $this->picture["id"] . "'
		");
		
		$this->picture_this($this->picture["id"]);
	}
	
	public function picture_file_version_next() {
		$this->picture_update("file_version",$this->picture["file_version"] + 1);
	}
	
	public function picture_delete() {
		$this->db->query("
			DELETE FROM	`fM_gallery_picture`
			WHERE		`id` = '" . $this->picture["id"] . "'
		");
		
		$this->picture = null;
	}
}
?>
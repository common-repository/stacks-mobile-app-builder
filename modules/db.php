<?php

class stacks_builder_db {

	private static $installing_transient = 'stacks_builder_installing';

	private static $tables_installed_option = 'stacks_builder_installed';

	public function init() {
		add_action('init', array(__CLASS__, 'install'), 5);

		add_action('wp', array($this, 'check_installed_tables'));
	}

	/**
	 * part of tables exists validation
	 *
	 * @return boolean
	 */
	public static function is_tables_installed() {
		$installed = get_option(self::$tables_installed_option);
		if ($installed === 'yes') {
			return true;
		}
		return false;
	}

	/**
	 * checks if tables already installed or not if we passed this stage or not
	 *
	 * @return boolean
	 */
	protected static function already_installed() {
		if (!is_blog_installed()) {
			return true;
		}

		// Check if we are not already running this routine.
		if ('yes' === get_transient(static::$installing_transient)) {
			return true;
		}
		if (static::is_tables_installed()) {
			return true;
		}
		return false;
	}

	/**
	 * Start Installation Process
	 *
	 * @return void
	 */
	public static function install() {
		if (self::already_installed()) {
			return;
		}
		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient(static::$installing_transient, 'yes', MINUTE_IN_SECONDS * 10);

		self::create_tables();

		update_option(self::$tables_installed_option, 'yes');
		delete_transient(static::$installing_transient);
	}

	/**
	 * Create new Tables
	 * @global object $wpdb
	 * @return boolean
	 */
	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta(self::get_schema());

		return true;
	}

	/**
	 * Generates Schema for additional tables
	 *
	 * @global object $wpdb
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ($wpdb->has_cap('collation')) {
			$collate = $wpdb->get_charset_collate();
		}
		$tables = '';
		return $tables;
	}

	public function check_installed_tables() {
		global $wpdb;
		// $table_name = $wpdb->base_prefix.'stacks_builder_settings';
		$tables_names = array(
			$wpdb->prefix . 'stacks_builder_views' => $this->create_table_stacks_builder_views(),
			$wpdb->prefix . 'stacks_builder_settings' => $this->create_table_stacks_builder_settings(),
		);
		foreach ($tables_names as $key => $table_name) {
			$query = $wpdb->prepare('SHOW TABLES LIKE %s', $key);
			if (!$wpdb->get_var($query) == $table_name) {
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($table_name);
			}
		}
	}

	public function create_table_stacks_builder_views() {
		global $wpdb;

		$collate = '';

		if ($wpdb->has_cap('collation')) {
			$collate = $wpdb->get_charset_collate();
		}
		$tables = "
            CREATE TABLE {$wpdb->prefix}stacks_builder_views (
			  id int UNSIGNED NOT NULL AUTO_INCREMENT,
			  project_id int UNSIGNED NOT NULL,
			  view_id int UNSIGNED NOT NULL,
			  view_name text NULL,
			  data LONGTEXT NULL,
			  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			  status text Null,
			  PRIMARY KEY (id),
			  UNIQUE KEY id (id)
			) $collate;
		";

		return $tables;
	}

	public function create_table_stacks_builder_settings() {
		global $wpdb;

		$collate = '';

		if ($wpdb->has_cap('collation')) {
			$collate = $wpdb->get_charset_collate();
		}
		$tables = "
            CREATE TABLE {$wpdb->prefix}stacks_builder_settings (
			  id int UNSIGNED NOT NULL,
			  app_settings text NULL,
			  content_settings text NULL,
			  style_settings text NULL,
			  apple_settings text NULL,
			  general_settings text NULL,
			  global_settings text NULL,
			  PRIMARY KEY (id),
			  UNIQUE KEY id (id)
			) $collate;
		";
		return $tables;
	}


	public function db_column_checker($table, $column_name, $type = 'VARCHAR(255) NULL', $modify = false) {
		global $wpdb;
		$row = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='{$table}' AND column_name='{$column_name}'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE {$table} ADD $column_name $type");
		}
		if($modify) {
			$row_modified = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='{$table}' AND column_name='{$column_name}' AND DATA_TYPE='{$type}'");
			if (empty($row_modified)) {
				$wpdb->query("ALTER TABLE {$table} MODIFY $column_name $type");
			}
		}
		return true;
	}
	/**
	 * Updates the user limit for the number of projects
	 */
	public function	update_user_projects_limit($user_id, $limit) {
		return update_user_meta($user_id, 'projects_limit', $limit);
	}

	/**
	 * Get Project Available Views
	 */
	public function get_views($project_id) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_views';
		return $wpdb->get_results(' 
				SELECT *
				FROM ' . $table . ' AS stacks_builder_views
				WHERE project_id = "' . $project_id . '"
				', ARRAY_A);
	}

	/**
	 * Get Project Available Views
	 */
	public function get_view($view_name, $project_id = 0) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_views';
		// Handle Old Projects
		if( !$project_id ) {
			return json_decode($wpdb->get_results(' 
			SELECT data
			FROM ' . $table . ' AS stacks_builder_views
			WHERE view_name = "' . $view_name . '"', ARRAY_A)[0]['data']);
		}
		return json_decode($wpdb->get_results(' 
		SELECT data
		FROM ' . $table . ' AS stacks_builder_views
		WHERE view_name = "' . $view_name . '" AND project_id = "' .$project_id. '" ', ARRAY_A)[0]['data']);
	}

	/**
	 * Insert/Update Project View
	 */
	public function update_view($view_id, $data, $project_id, $view_name, $status = 'active') {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_views';
		$this->db_column_checker($table, 'data', 'LONGTEXT NULL', true);
		$data = json_encode($data);
		$project = $wpdb->get_results( sprintf( "SELECT * FROM {$table} WHERE view_name = '{$view_name}' AND project_id = %d", $project_id ), ARRAY_A );
		if( $project ) {
			$wpdb->update( $table, array( 'data' => $data, 'updated_at' => date("Y-m-d H:i:s"), 'status' => $status ), array( 'view_name' => $view_name, 'project_id' => $project_id ) );
		} else {
			$wpdb->insert(
				$table,
				array(
					'project_id' => $project_id,
					'view_id' => $view_id,
					'view_name' => $view_name,
					'data' => $data,
					'created_at' => date("Y-m-d H:i:s"),
					'updated_at' => date("Y-m-d H:i:s"),
					'status' => $status,
				)
			);
		}

		return $wpdb->insert_id;
	}

	public function get_project_settings($project_id) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_settings';
		$project_settings = $wpdb->get_results(sprintf("SELECT * FROM {$table} WHERE id = %d", $project_id), ARRAY_A);
		return $project_settings;
	}

	public function update_project_app_settings($project_id, $app_settings) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_settings';
		$project = $wpdb->get_results(sprintf("SELECT * FROM {$table} WHERE id = %d", $project_id), ARRAY_A);
		if ($project) {
			$wpdb->update($table, array('app_settings' => $app_settings), array('id' => $project_id));
		} else {
			$wpdb->insert(
				$table,
				array(
					'id'        				=> $project_id,
					'app_settings'      => $app_settings,
				)
			);
		}

		return $wpdb->insert_id;
	}

	public function update_project_content_settings($project_id, $content_settings) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_settings';
		$project = $wpdb->get_results(sprintf("SELECT * FROM {$table} WHERE id = %d", $project_id), ARRAY_A);

		if ($project) {
			$old_content_settings = (array) json_decode($project[0]['content_settings']);
			// Merge old content settings with new content settings not to lose unchanged imgs as they aren't submitted in new content settings
			$merged_content_settings = array_merge($old_content_settings, (array) json_decode($content_settings));
			$wpdb->update($table, array('content_settings' => json_encode($merged_content_settings)), array('id' => $project_id));
		} else {
			$wpdb->insert(
				$table,
				array(
					'id'        => $project_id,
					'content_settings'      => $content_settings,
				)
			);
		}

		return $wpdb->insert_id;
	}

	public function update_project_general_settings($project_id, $general_settings) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_settings';
		$project = $wpdb->get_results(sprintf("SELECT * FROM {$table} WHERE id = %d", $project_id), ARRAY_A);
		if ($project) {
			$wpdb->update($table, array('general_settings' => $general_settings), array('id' => $project_id));
		} else {
			$wpdb->insert(
				$table,
				array(
					'id'        => $project_id,
					'general_settings'      => $general_settings,
				)
			);
		}

		return $wpdb->insert_id;
	}

	public function update_project_global_settings($project_id, $global_settings) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_settings';
		$project = $wpdb->get_results(sprintf("SELECT * FROM {$table} WHERE id = %d", $project_id), ARRAY_A);
		if ($project) {
			$wpdb->update($table, array('global_settings' => $global_settings), array('id' => $project_id));
		} else {
			$wpdb->insert(
				$table,
				array(
					'id'       				=> $project_id,
					'global_settings'      => $global_settings,
				)
			);
		}

		return $wpdb->insert_id;
	}

	public function update_project_apple_settings($project_id, $apple_settings) {
		global $wpdb;
		$table = $wpdb->prefix . 'stacks_builder_settings';
		// Check Column Existance
		$this->db_column_checker($table, 'apple_settings');
		$project = $wpdb->get_results(sprintf("SELECT * FROM {$table} WHERE id = %d", $project_id), ARRAY_A);
		if ($project) {
			$wpdb->update($table, array('apple_settings' => $apple_settings), array('id' => $project_id));
		} else {
			$wpdb->insert(
				$table,
				array(
					'id'        => $project_id,
					'apple_settings'      => $apple_settings,
				)
			);
		}

		return $wpdb->insert_id;
	}

	public function delete_all_views($project_id) {
		global $wpdb ;
		$table = $wpdb->prefix.'stacks_builder_views';
		$wpdb->delete( $table, array( 'project_id' => $project_id ) );
	}
}


$GLOBALS['stacks_builder'] = new stacks_builder_db();
$GLOBALS['stacks_builder']->init();

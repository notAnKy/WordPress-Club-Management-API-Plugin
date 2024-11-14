<?php
/**
 * Plugin Name: club rest API
 * Description: Provides a REST API for managing clubs and members
 * Author: med ali jemmali
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rest_Api {
    private $key;
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        add_action('club_v1_key_expire', array($this, 'expire_key'));
    }

    public function plugin_activation() {
        $this->create_clubs_table();
        $this->create_keys_table();
        $this->create_members_table();
        $this->create_trash_table();
    }

    //tabels creation
    private function create_clubs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'clubs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            club_id INT NOT NULL AUTO_INCREMENT,
            club_name VARCHAR(255) NOT NULL,
            owner_id INT,
            postal_code INT,
            phone VARCHAR(255) NOT NULL,
            mail VARCHAR(255) NOT NULL,
            address VARCHAR(255) NOT NULL,
            PRIMARY KEY (club_id)
        )";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private function create_members_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'members';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            member_id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            club_id INT,
            date_of_birth DATE,
            place VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL,
            term_condition ENUM('Regular', 'Special needs') DEFAULT 'Regular',
            genre VARCHAR(255) NOT NULL,
            address VARCHAR(255) NOT NULL,
            educational_institution VARCHAR(255) NOT NULL,
            PRIMARY KEY (member_id),
            FOREIGN KEY (club_id) REFERENCES {$wpdb->prefix}clubs(club_id)
        )";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private function create_trash_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'trash';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            trash_id INT NOT NULL AUTO_INCREMENT,
            datetime DATETIME NOT NULL,
            description TEXT,
            PRIMARY KEY (trash_id)
        )";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    private function create_keys_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'keys';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            key_code VARCHAR(255) NOT NULL,
            action ENUM('valid', 'invalid') DEFAULT 'valid',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    //register_routes
    public function register_rest_routes() {
        register_rest_route('club/v1', '/generate-key', array(
            'methods'  => 'POST',
            'callback' => array($this, 'generate_key'),
        ));
        register_rest_route('club/v1', '/add', array(
            'methods'  => 'POST',
            'callback' => array($this, 'add_club'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/update', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_club'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/remove', array(
            'methods'  => 'POST',
            'callback' => array($this, 'remove_club'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/get-all-clubs-with-members', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_all_clubs_with_members'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/get-members-by-club/(?P<club_id>\d+)', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_members_by_club'),
            'args'     => array(
                'club_id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/get-club-owners', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_club_owners'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/get-club-owner-details/(?P<club_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_club_owner_details'),
            'args' => array(
                'club_id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/edit-club-owner', array(
            'methods'  => 'POST',
            'callback' => array($this, 'edit_club_owner_details'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/delete-club-owner', array(
            'methods'  => 'POST',
            'callback' => array($this, 'delete_club_owner'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/add-member', array(
            'methods'  => 'POST',
            'callback' => array($this, 'add_member'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/remove-member', array(
            'methods'  => 'POST',
            'callback' => array($this, 'remove_member'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
        register_rest_route('club/v1', '/update-member', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_member'),
            'permission_callback' => array($this, 'check_key_permission'),
        ));
    }

    //key_generation
    public function generate_key($request) {
        $parameters = $request->get_params();
        $user_login = isset($parameters['user_login']) ? $parameters['user_login'] : '';
        $user_pass = isset($parameters['user_pass']) ? $parameters['user_pass'] : '';
        if (empty($user_login) || empty($user_pass)) {
            return new WP_Error('invalid_credentials', 'User login and password are required', array('status' => 400));
        }
        $user = wp_authenticate($user_login, $user_pass);
        if (is_wp_error($user)) {
            return new WP_Error('authentication_failed', 'User authentication failed', array('status' => 401));
        }
        $key = wp_generate_password(20, false);
        global $wpdb;
        $table_name = $wpdb->prefix . 'keys';
        $data = array(
            'key_code' => $key,
            'action' => 'valid',
        );
        $format = array(
            '%s',
            '%s',
        );
        $wpdb->insert($table_name, $data, $format);
        wp_schedule_single_event(time() + 2 * HOUR_IN_SECONDS, 'club_v1_key_expire', array($key));
        return array(
            'status' => 'success',
            'message' => 'Key generated successfully',
            'key' => $key,
        );
    }

    public function check_key_permission($request) {
        $key = $request->get_param('key');
        if (!$this->is_valid_key($key)) {
            return new WP_Error('invalid_key', 'Invalid key', array('status' => 401));
        }
        return true;
    }

    public function is_valid_key($key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'keys';
        $result = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE key_code = %s AND action = 'valid'", $key));
        return $result > 0;
    }

    public function expire_key($key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'keys';
        $wpdb->update(
            $table_name,
            array('action' => 'invalid'), 
            array('key_code' => $key)
        );
    }

    //verification
    private function is_valid_owner($owner_id) {
        global $wpdb;
        $owner_exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID = %d", $owner_id));
        if (!$owner_exists) {
            return false;
        }
        $club_exists = $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$wpdb->prefix}clubs WHERE owner_id = %d", $owner_id));
        if ($club_exists) {
            return false;
        }
        return true;
    }

    private function is_valid_owner_for_update($new_owner_id, $club_id) {
        global $wpdb;
        $owner_exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID = %d", $new_owner_id));
        if (!$owner_exists) {
            return false;
        }
        $club_exists = $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$wpdb->prefix}clubs WHERE owner_id = %d AND club_id != %d", $new_owner_id, $club_id));
        if ($club_exists) {
            return false;
        }
        return true;
    }

    private function get_club_by_id($club_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'clubs';
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE club_id = %d", $club_id);
        return $wpdb->get_row($query);
    }

    private function get_member_by_id($member_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'members';
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE member_id = %d", $member_id);
        return $wpdb->get_row($query);
    }

    //add_club
    public function add_club($request) {
        $club_data = $request->get_params();
        $owner_id = intval($club_data['owner_id']);
        if (!$this->is_valid_owner($owner_id)) {
            return new WP_Error('invalid_owner', 'Invalid owner_id or owner is already associated with another club', array('status' => 400));
        }
        
        $club_name = sanitize_text_field($club_data['club_name']);
        $postal_code = intval($club_data['postal_code']);
        $phone = sanitize_text_field($club_data['phone']);
        $mail = sanitize_text_field($club_data['mail']);
        $address = sanitize_text_field($club_data['address']);        
        $club_id = $this->insert_club($club_name, $owner_id, $postal_code, $phone, $mail, $address);
        
        if ($club_id) {
            return new WP_REST_Response(array('message' => 'Club added successfully', 'club_id' => $club_id), 200);
        } else {
            return new WP_Error('club_not_added', 'Error adding the club', array('status' => 500));
        }
    }
    
    private function insert_club($club_name, $owner_id, $postal_code, $phone, $mail, $address) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'clubs';
        $wpdb->insert(
            $table_name,
            array(
                'club_name'   => $club_name,
                'owner_id'    => $owner_id,
                'postal_code' => $postal_code,
                'phone'       => $phone,
                'mail'        => $mail,
                'address'     => $address,
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s')
        );
        return $wpdb->insert_id;
    }    

    //update_club
    public function update_club($request) {
        $club_data = $request->get_params();
        $club_id = intval($club_data['club_id']);
        $existing_club = $this->get_club_by_id($club_id);
        if (!$existing_club) {
            return new WP_Error('club_not_found', 'Club not found', array('status' => 404));
        }
        $new_owner_id = isset($club_data['owner_id']) ? intval($club_data['owner_id']) : $existing_club->owner_id;
        if (!$this->is_valid_owner_for_update($new_owner_id, $club_id)) {
            return new WP_Error('invalid_owner', 'Invalid owner_id or owner is already associated with another club', array('status' => 400));
        }
        $club_name = isset($club_data['club_name']) ? sanitize_text_field($club_data['club_name']) : $existing_club->club_name;
        $postal_code = isset($club_data['postal_code']) ? sanitize_text_field($club_data['postal_code']) : $existing_club->postal_code;
        $phone = isset($club_data['phone']) ? sanitize_text_field($club_data['phone']) : $existing_club->phone;
        $mail = isset($club_data['mail']) ? sanitize_text_field($club_data['mail']) : $existing_club->mail;
        $address = isset($club_data['address']) ? sanitize_text_field($club_data['address']) : $existing_club->address;
        $success = $this->update_club_data($club_id, $club_name, $postal_code, $phone , $mail, $address, $new_owner_id);
        if ($success) {
            return new WP_REST_Response(array('message' => 'Club updated successfully', 'club_id' => $club_id), 200);
        } else {
            return new WP_Error('club_not_updated', 'Error updating the club', array('status' => 500));
        }
    }

    private function update_club_data($club_id, $club_name, $postal_code, $phone , $mail, $address, $owner_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'clubs';
        $success = $wpdb->update(
            $table_name,
            array(
                'club_name'   => $club_name,
                'postal_code' => $postal_code,
                'phone'    => $phone,
                'mail'    => $mail,
                'address'    => $address,
                'owner_id'    => $owner_id,
            ),
            array('club_id' => $club_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        return $success !== false;
    }

    //remove_club
    public function remove_club($request) {
        $club_data = $request->get_params();
        $club_id = isset($club_data['club_id']) ? intval($club_data['club_id']) : 0;
        $existing_club = $this->get_club_by_id($club_id);
    
        if (!$existing_club) {
            return new WP_Error('club_not_found', 'Club not found', array('status' => 404));
        }
    
        $members_removed = $this->remove_all_members_to_trash($club_id);
    
        if ($members_removed !== false) {
            $club_moved_to_trash = $this->move_club_to_trash($club_id);
    
            if ($club_moved_to_trash !== false) {
                return new WP_REST_Response(array('message' => 'Club moved to trash successfully', 'club_id' => $club_id), 200);
            } else {
                return new WP_Error('club_not_moved_to_trash', 'Error moving the club to trash', array('status' => 500));
            }
        } else {
            return new WP_Error('members_not_removed', 'Error removing members of the club', array('status' => 500));
        }
    }
    
    private function remove_all_members_to_trash($club_id) {
        global $wpdb;
        $table_name_members = $wpdb->prefix . 'members';
        $table_name_trash = $wpdb->prefix . 'trash';
        $members = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_members WHERE club_id = %d", $club_id), ARRAY_A);
        $removed_members_info = array();
    
        foreach ($members as $member) {
            $wpdb->insert($table_name_trash, array(
                'datetime'    => current_time('mysql'),
                'description' => 'Member removed from club',
                'details'     => json_encode($member),
            ));
            $removed_members_info[] = $member;
        }
    
        $success = $wpdb->delete($table_name_members, array('club_id' => $club_id), array('%d'));
    
        return $success !== false ? $removed_members_info : false;
    }
    
    private function move_club_to_trash($club_id) {
        global $wpdb;
        $table_name_clubs = $wpdb->prefix . 'clubs';
        $table_name_trash = $wpdb->prefix . 'trash';
        $club = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_clubs WHERE club_id = %d", $club_id), ARRAY_A);
        $wpdb->insert($table_name_trash, array(
            'datetime'    => current_time('mysql'),
            'description' => 'Club removed: ' . json_encode($club),
        ));
    
        $success = $wpdb->delete($table_name_clubs, array('club_id' => $club_id), array('%d'));
    
        return $success !== false ? $club : false;
    }    

    //get_members
    public function get_all_clubs_with_members($request) {
        $page = $request->get_param('page') ?: 1;
        $per_page = max(1, $request->get_param('per_page') ?: 10);
        $offset = ($page - 1) * $per_page;
        global $wpdb;
        $clubs_table = $wpdb->prefix . 'clubs';
        $members_table = $wpdb->prefix . 'members';
    
        $query = "SELECT c.club_id, c.club_name, c.owner_id, m.*
                FROM $clubs_table c
                LEFT JOIN $members_table m ON c.club_id = m.club_id
                LIMIT $per_page OFFSET $offset";
        $results = $wpdb->get_results($query, ARRAY_A);
    
        if (!$results) {
            return new WP_Error('no_clubs_found', 'No clubs found', array('status' => 404));
        }
    
        $clubs_with_members = array();
        foreach ($results as $row) {
            $club_id = $row['club_id'];
            if (!isset($clubs_with_members[$club_id])) {
                $clubs_with_members[$club_id] = array(
                    'club_name'   => $row['club_name'],
                    'owner_id'    => $row['owner_id'],
                    'members'     => array(),
                );
            }
            if (isset($row['member_id']) && $row['member_id'] !== null) {
                $clubs_with_members[$club_id]['members'][] = array(
                    'member_id'          => $row['member_id'],
                    'name'               => $row['name'],
                    'date_of_birth'      => $row['date_of_birth'],
                    'place'              => $row['place'],
                    'status'             => $row['status'],
                    'term_condition'     => $row['term_condition'],
                    'genre'              => $row['genre'],
                    'address'            => $row['address'],
                    'educational_institution' => $row['educational_institution'],
                );
            }
        }
    
        return new WP_REST_Response(array_values($clubs_with_members), 200);
    }     
     
    //get_members_by_club
    public function get_members_by_club($request) {
        $club_id = intval($request->get_param('club_id'));
        $existing_club = $this->get_club_by_id($club_id);
        if (!$existing_club) {
            return new WP_Error('club_not_found', 'Club not found', array('status' => 404));
        }
    
        $page = max(1, intval($request->get_param('page')));
        $per_page = max(1, intval($request->get_param('per_page') ?: 10));
        global $wpdb;
        $clubs_table = $wpdb->prefix . 'clubs';
        $members_table = $wpdb->prefix . 'members';
        $offset = ($page - 1) * $per_page;
        $query = $wpdb->prepare("SELECT c.club_id, c.club_name, m.*
                                FROM $clubs_table c
                                LEFT JOIN $members_table m ON c.club_id = m.club_id
                                WHERE c.club_id = %d
                                LIMIT %d OFFSET %d", $club_id, $per_page, $offset);
        $results = $wpdb->get_results($query, ARRAY_A);
        if (!$results) {
            return new WP_Error('no_members_found', 'No members found for the specified club', array('status' => 404));
        }
    
        $club_with_members = array();
        foreach ($results as $row) {
            $club_id = $row['club_id'];
            if (!isset($club_with_members['club_id'])) {
                $club_with_members = array(
                    'club_id' => $club_id,
                    'club_name' => $row['club_name'],
                    'members' => array(),
                );
            }
            if (isset($row['member_id']) && $row['member_id'] !== null) {
                $club_with_members['members'][] = array(
                    'member_id' => $row['member_id'],
                    'name' => $row['name'],
                    'date_of_birth' => $row['date_of_birth'],
                    'place' => $row['place'],
                    'status' => $row['status'],
                    'term_condition' => $row['term_condition'],
                    'genre' => $row['genre'],
                    'address' => $row['address'],
                    'educational_institution' => $row['educational_institution'],
                );
            }
        }
    
        return new WP_REST_Response($club_with_members, 200);
    }
    

    //get_club_owners
    public function get_club_owners($request) {
        $page = max(1, intval($request->get_param('page')));
        $per_page = max(1, intval($request->get_param('per_page') ?: 10));
        global $wpdb;
        $clubs_table = $wpdb->prefix . 'clubs';
        $offset = ($page - 1) * $per_page;
        $query = $wpdb->prepare("SELECT DISTINCT c.owner_id, u.display_name as owner_name, u.user_email as owner_email
                    FROM $clubs_table c
                    LEFT JOIN {$wpdb->users} u ON c.owner_id = u.ID
                    LIMIT %d OFFSET %d", $per_page, $offset);
        $results = $wpdb->get_results($query, ARRAY_A);
        if (!$results) {
            return new WP_Error('no_owners_found', 'No club owners found', array('status' => 404));
        }
        $club_owners = array();
        foreach ($results as $row) {
            $club_owners[] = array(
                'owner_id' => $row['owner_id'],
                'owner_name' => $row['owner_name'],
                'owner_email' => $row['owner_email'],
            );
        }
        return new WP_REST_Response($club_owners, 200);
    }

    //get-club-owner-details
    public function get_club_owner_details($request) {
        $club_id = intval($request->get_param('club_id'));
        $existing_club = $this->get_club_by_id($club_id);
        if (!$existing_club) {
            return new WP_Error('club_not_found', 'Club not found', array('status' => 404));
        }
        global $wpdb;
        $clubs_table = $wpdb->prefix . 'clubs';
        $query = $wpdb->prepare("SELECT c.owner_id, u.display_name as owner_name, u.user_email as owner_email
                                 FROM $clubs_table c
                                 LEFT JOIN {$wpdb->users} u ON c.owner_id = u.ID
                                 WHERE c.club_id = %d", $club_id);
        $result = $wpdb->get_row($query, ARRAY_A);
        if (!$result) {
            return new WP_Error('club_not_found', 'Club not found or owner details not available', array('status' => 404));
        }
        $club_owner_details = array(
            'owner_id' => $result['owner_id'],
            'owner_name' => $result['owner_name'],
            'owner_email' => $result['owner_email'],
        );
        return new WP_REST_Response($club_owner_details, 200);
    }
    
    //delete-club-owner
    public function delete_club_owner($request) {
        $request_data = $request->get_params();
        $club_id = intval($request_data['club_id']);
        $existing_club = $this->get_club_by_id($club_id);
        if (!$existing_club) {
            return new WP_Error('club_not_found', 'Club not found', array('status' => 404));
        }
        global $wpdb;
        $clubs_table = $wpdb->prefix . 'clubs';
        $success = $wpdb->update(
            $clubs_table,
            array('owner_id' => null),
            array('club_id' => $club_id),
            array('%d'),
            array('%d')
        );
        if ($success !== false) {
            return new WP_REST_Response(array('message' => 'Club owner deleted successfully', 'club_id' => $club_id), 200);
        } else {
            return new WP_Error('owner_not_deleted', 'Error deleting the club owner', array('status' => 500));
        }
    }

    //add_member
    public function add_member($request) {
        $member_data = $request->get_params();
        $club_id = isset($member_data['club_id']) ? intval($member_data['club_id']) : 0;
        $existing_club = $this->get_club_by_id($club_id);
        if (!$existing_club) {
            return new WP_Error('club_not_found', 'Club not found', array('status' => 404));
        }

        $name = isset($member_data['name']) ? sanitize_text_field($member_data['name']) : '';
        $date_of_birth = isset($member_data['date_of_birth(??-??-????)']) ? date('Y-m-d', strtotime($member_data['date_of_birth(??-??-????)'])) : '';
        $place = isset($member_data['place']) ? sanitize_text_field($member_data['place']) : '';
        $status = isset($member_data['status']) ? sanitize_text_field($member_data['status']) : '';
        $term_condition = isset($member_data['term_condition(Regular,Special_needs)']) ? sanitize_text_field($member_data['term_condition(Regular,Special_needs)']) : 'Regular';
        $genre = isset($member_data['genre']) ? sanitize_text_field($member_data['genre']) : '';
        $address = isset($member_data['address']) ? sanitize_text_field($member_data['address']) : '';
        $educational_institution = isset($member_data['educational_institution']) ? sanitize_text_field($member_data['educational_institution']) : '';

        if (empty($name)) {
            return new WP_Error('invalid_data', 'Name is a required field for adding a member', array('status' => 400));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'members';
        $wpdb->insert(
            $table_name,
            array(
                'name'                   => $name,
                'club_id'                => $club_id,
                'date_of_birth'          => $date_of_birth,
                'place'                  => $place,
                'status'                 => $status,
                'term_condition'         => $term_condition,
                'genre'                  => $genre,
                'address'                => $address,
                'educational_institution'=> $educational_institution,
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        $member_id = $wpdb->insert_id;

        if ($member_id) {
            return new WP_REST_Response(array('message' => 'Member added successfully', 'member_id' => $member_id), 200);
        } else {
            $wpdb_error_message = $wpdb->last_error;
            return new WP_Error('member_not_added', 'Error adding the member: ' . $wpdb_error_message, array('status' => 500));
        }
    } 

    //remove_member
    public function remove_member($request) {
        $member_data = $request->get_params();
        $member_id = isset($member_data['member_id']) ? intval($member_data['member_id']) : 0;
        $existing_member = $this->get_member_by_id($member_id);
        if (!$existing_member) {
            return new WP_Error('member_not_found', 'Member not found', array('status' => 404));
        }
        $success = $this->delete_member($member_id);
        if ($success) {
            return new WP_REST_Response(array('message' => 'Member removed successfully', 'member_id' => $member_id), 200);
        } else {
            return new WP_Error('member_not_removed', 'Error removing the member', array('status' => 500));
        }
    }
    
    private function delete_member($member_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'members';
        $success = $wpdb->delete($table_name, array('member_id' => $member_id), array('%d'));
        return $success !== false;
    }

    //update_memeber
    public function update_member($request) {
        $member_data = $request->get_params();
        $member_id = isset($member_data['member_id']) ? intval($member_data['member_id']) : 0;
        $existing_member = $this->get_member_by_id($member_id);
        if (!$existing_member) {
            return new WP_Error('member_not_found', 'Member not found', array('status' => 404));
        }
        $name = isset($member_data['name']) ? sanitize_text_field($member_data['name']) : $existing_member->name;
        $date_of_birth = isset($member_data['date_of_birth']) ? sanitize_text_field($member_data['date_of_birth']) : $existing_member->date_of_birth;
        $place = isset($member_data['place']) ? sanitize_text_field($member_data['place']) : $existing_member->place;
        $status = isset($member_data['status']) ? sanitize_text_field($member_data['status']) : $existing_member->status;
        $term_condition = isset($member_data['term_condition']) ? sanitize_text_field($member_data['term_condition']) : $existing_member->term_condition;
        $genre = isset($member_data['genre']) ? sanitize_text_field($member_data['genre']) : $existing_member->genre;
        $address = isset($member_data['address']) ? sanitize_text_field($member_data['address']) : $existing_member->address;
        $educational_institution = isset($member_data['educational_institution']) ? sanitize_text_field($member_data['educational_institution']) : $existing_member->educational_institution;
        $success = $this->update_member_data($member_id, $name, $date_of_birth, $place, $status, $term_condition, $address, $educational_institution,$genre);
        if ($success) {
            return new WP_REST_Response(array('message' => 'Member updated successfully', 'member_id' => $member_id), 200);
        } else {
            return new WP_Error('member_not_updated', 'Error updating the member', array('status' => 500));
        }
    }
    
    private function update_member_data($member_id, $name, $date_of_birth, $place, $status, $term_condition, $address, $educational_institution,$genre) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'members';
        $success = $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'date_of_birth' => $date_of_birth,
                'place' => $place,
                'status' => $status,
                'term_condition' => $term_condition,
                'address' => $address,
                'genre' => $genre,
                'educational_institution' => $educational_institution,
            ),
            array('member_id' => $member_id),
            array('%s', '%s'),
            array('%d')
        );
        return $success !== false;
    }
}

new Rest_Api();
?>
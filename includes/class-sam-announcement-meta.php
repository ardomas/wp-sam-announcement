<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Sam_Announcement_Meta {

    // meta keys mapping
    private $meta_keys = [
        'sub_title'   => '_sa_sub_title',
        'image'       => '_sa_image',
        'start_date'  => '_sa_start_date',
        'end_date'    => '_sa_end_date',
        'start_time'  => '_sa_start_time',
        'end_time'    => '_sa_end_time',
        'content'     => '_sa_content',     // main content
        'contacts'    => '_sa_contacts',    // array of arrays
        'price_label' => '_sa_price_label',
        'prices'      => '_sa_prices',      // array of arrays
        'location'    => '_sa_location',
        'location_pin'=> '_sa_location_pin',
        'speakers'    => '_sa_speakers',    // array of arrays
        'organizers'  => '_sa_organizers'   // array of arrays
    ];

    public function __construct() {
        // $this->init();
    }

    public function init() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
        // add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // Hapus editor bawaan WordPress untuk CPT announcement
        add_action('admin_init', function() {
            remove_post_type_support('announcement', 'editor');
        });
    }

    public function add_meta_box() {
        add_meta_box(
            'sa_announcement_meta',
            __( 'Announcement Details', 'sam-announcement' ),
            [ $this, 'render_meta_box' ],
            'announcement',
            'normal',
            'high'
        );
    }

    public function enqueue_admin_scripts($hook) {
        global $post;
        if (empty($post) || $post->post_type !== 'announcement') return;

        wp_enqueue_media(); // <<< penting, ini untuk wp.media
        wp_enqueue_script(
            'sam-announcement-admin',
            plugin_dir_url( dirname(__FILE__) ) . 'js/admin.js', // karena class ada di include/
            array('jquery'),
            '1.0',
            true
        );
    }

    public function render_meta_box( $post ) {
        // nonce
        wp_nonce_field( 'sa_save_meta', 'sa_meta_nonce' );

        // load stored meta
        $m = [];
        foreach ( $this->meta_keys as $k => $meta_key ) {
            $m[ $k ] = get_post_meta( $post->ID, $meta_key, true );
        }

        // normalize arrays
        if ( ! is_array( $m['speakers'] ) ) $m['speakers'] = $m['speakers'] ? $m['speakers'] : [];
        if ( ! is_array( $m['organizers'] ) ) $m['organizers'] = $m['organizers'] ? $m['organizers'] : [];

        // Sub Title
        ?>

        <!-- Sub Title -->
        <div class="sa-row">
            <label>Sub Title (optional)</label><br/>
            <input type="text" name="sa_sub_title" style="width:100%;" value="<?php echo esc_attr( $m['sub_title'] ); ?>" />
        </div>

        <!-- Render: Start & End Date/Time -->
        <!-- <strong>Start & End Date/Time</strong>"> -->
        <div class="sa-row" style="display: block;">

            <!-- Render inputs (HTML5 date) -->
            <div class="sa-col" style="display: inline-block;">
                <div class="sa-row">
                    <label>Date</label><br>
                    <input type="date" name="sa_start_date" class="sa-small" value="<?php echo esc_attr( $m['start_date'] ); ?>" />
                    <input type="date" name="sa_end_date"   class="sa-small" value="<?php echo esc_attr( $m['end_date']   ); ?>" />
                </div>
            </div>

            <!-- Render inputs (HTML5 time) -->
            <div class="sa-col" style="display: inline-block;">
                <div class="sa-row">
                    <label>Times</label><br>
                    <input type="time" name="sa_start_time" class="sa-small" value="<?php echo esc_attr( $m['start_time'] ); ?>" />
                    <input type="time" name="sa_end_time"   class="sa-small" value="<?php echo esc_attr( $m['end_time']   ); ?>" />
                </div>
            </div>

        </div>

        <div class="sa-row" style="width: 100%; display: inline">

            <?php

                // Image upload
                $image_id = $m['image'] ?? '';
                $image_url = $image_id ? wp_get_attachment_url($image_id) : '';

            ?>
            <div class="sa-col" style="width: 39%; min-width: 200px; display: inline-block; vertical-align: top;">
                <!-- Image upload -->
                <div class="sa-row"><label>Announcement Image</label><br>
                    <img id="sa_image_preview" src="<?php echo esc_url($image_url); ?>" style="max-width:200px; display:block; margin-bottom:6px;" />
                    <input type="hidden" name="sa_image" id="sa_image" value="<?php echo esc_attr($image_id); ?>" />
                    <button type="button" class="button" id="sa_image_upload">Upload / Select Image</button>
                    <button type="button" class="button" id="sa_image_remove">Remove Image</button>
                </div>
            </div>

            <div class="sa-col" style="width: 59%; min-width: 300px; display: inline-block; vertical-align: top;">

                <!-- Main Content  -->
                <!--  Content (WYSIWYG) -->
                <div class="sa-row"><label>Main Content</label><br><?php

                    $content_value = $m['content'] ?? '';
                    wp_editor(
                        $content_value,         // isi awal
                        'sa_content',           // ID textarea
                        [
                            'textarea_name' => 'sa_content', // nama field saat submit
                            'media_buttons' => true,        // tombol media
                            'textarea_rows' => 10,          // tinggi editor
                            'teeny'         => false,       // versi mini editor
                        ]
                    );

                ?></div>


            </div>

        </div>

        <!-- Contacts repeater -->
        <div class="sa-row"><label>Contacts</label><div id="sa-contacts-container">
            <?php if ( empty( $m['contacts'] ) ) { ?>
                <div class="sa-contact"><input type="text" name="sa_contacts_name[]" placeholder="Name" />
                    <input type="text" name="sa_contacts_phone[]" placeholder="Phone / WhatsApp" />
                    <button type="button" class="button sa-remove">Remove</button>
                </div>
            <?php } else { 
                foreach ( $m['contacts'] as $contact ) {
                    $cname = $contact['name'] ?? '';
                    $cphone = $contact['phone'] ?? ''; 
            ?>
                    <div class="sa-contact">
                        <input type="text" name="sa_contacts_name[]" placeholder="Name" value="<?php echo esc_attr($cname); ?>" />
                        <input type="text" name="sa_contacts_phone[]" placeholder="Phone / WhatsApp" value="<?php echo esc_attr($cphone); ?>" />
                        <button type="button" class="button sa-remove">Remove</button>
                    </div>
            <?php
                }
            }
            ?>
        </div>
        <div class="sa-repeater-add">
            <button type="button" class="button sa-add-contact">+ Add Contact</button>
        </div>

        <br/>
        <!-- Price Label - (satu input saja)-->
        <?php $price_label = $m['price_label'] ?? 'Investasi'; ?>
        <div class="sa-row"><label>Price Label</label><br>
            <input type="text" name="sa_price_label" value="<?php echo esc_attr($price_label); ?>" style="width:30%;" />
            <p class="description">E.g. Investasi, Tiket Masuk, Biaya Administrasi</p>
        </div>

        <!-- prices repeater -->
        <?php $prices = $m['prices'] ?? []; ?>

        <div class="sa-row">
            <label>Prices</label><div id="sa-prices-container">
            <?php if(empty($prices)) { ?>
                <div class="sa-price">
                    <input type="number" name="sa_prices_value[]" placeholder="Price" />
                    <input type="text" name="sa_prices_name[]" placeholder="Category / Description" />
                    <button type="button" class="button sa-remove">Remove</button>
                </div>
            <?php } else {  ?>
            <?php foreach($prices as $p) {
                $name  = $p['name'] ?? '';
                $value = $p['value'] ?? '';
            ?>
                <div class="sa-price">
                    <input type="number" name="sa_prices_value[]" placeholder="Price" value="<?php echo esc_attr($value); ?>" />
                    <input type="text" name="sa_prices_name[]" placeholder="Category / Description" value="<?php echo esc_attr($name); ?>" />
                    <button type="button" class="button sa-remove">Remove</button>
                </div>
            <?php

                }
            }

            ?>
        </div>
        <div class="sa-repeater-add"><button type="button" class="button sa-add-price">+ Add Price</button></div>

        <!-- Location and Pin -->
        <br/>
        <div class="sa-row"><label>Location</label><br/>
            <input type="text" name="sa_location" style="width:60%;" value="<?php echo esc_attr( $m['location'] ); ?>" />
            <input type="text" name="sa_location_pin" placeholder="Google Maps URL or lat,lng" style="width:35%;" value="<?php echo esc_attr( $m['location_pin'] ); ?>" />
            <p class="description">You can paste a Google Maps share URL or coordinates (lat,lng).</p>
        </div>


        <!-- Speakers repeaters -->
        <br/>
        <div class="sa-row"><label>Speakers</label><div id="sa-speakers-container">
            <?php
            // Speakers repeater
            if ( empty( $m['speakers'] ) ) {
                ?>
                <div class="sa-speaker"><input type="text" name="sa_speakers_name[]" placeholder="Name" />
                    <input type="text" name="sa_speakers_prof[]" placeholder="Profession" />
                    <input type="text" name="sa_speakers_org[]" placeholder="Organization" />
                    <button type="button" class="button sa-remove">Remove</button></div>
                <?php
            } else {
                foreach ( $m['speakers'] as $sp ) {
                    $name = isset($sp['name']) ? $sp['name'] : '';
                    $prof = isset($sp['profession']) ? $sp['profession'] : '';
                    $org  = isset($sp['organization']) ? $sp['organization'] : '';
                ?>
                <div class="sa-speaker">
                    <input type="text" name="sa_speakers_name[]" placeholder="Name" value="<?php echo esc_attr($name) ?>" />
                    <input type="text" name="sa_speakers_prof[]" placeholder="Profession" value="<?php echo esc_attr($prof); ?>" />
                    <input type="text" name="sa_speakers_org[]" placeholder="Organization" value="<?php echo esc_attr($org); ?>" />
                    <button type="button" class="button sa-remove">Remove</button>
                </div>
                <?php }
            }
            ?>
        </div>
        <div class="sa-repeater-add"><button type="button" class="button sa-add-speaker">+ Add Speaker</button></div>

        <br/>
        <div class="sa-row"><label>Organizers (primary first)</label><div id="sa-organizers-container">
        <?php
        // Organizers repeater
        if ( empty( $m['organizers'] ) ) { 
        ?>
            <div class="sa-organizer"><input type="text" name="sa_organizers_name[]" placeholder="Organizer name" />
                <input type="text" name="sa_organizers_desc[]" placeholder="Description (optional)" />
                <button type="button" class="button sa-remove">Remove</button>
            </div>
        <?php
        } else {
            foreach ( $m['organizers'] as $org ) {
                $oname = isset($org['name']) ? $org['name'] : '';
                $odesc = isset($org['description']) ? $org['description'] : '';
        ?>
            <div class="sa-organizer">
                <input type="text" name="sa_organizers_name[]" placeholder="Organizer name" value="<?php echo esc_attr($oname); ?>" />
                <input type="text" name="sa_organizers_desc[]" placeholder="Description (optional)" value="<?php echo esc_attr($odesc); ?>" />
                <button type="button" class="button sa-remove">Remove</button>
            </div>
        <?php
            }
        }
        ?>
        </div>
        <div class="sa-repeater-add"><button type="button" class="button sa-add-organizer">+ Add Organizer</button></div>
        <!-- echo '</div>'; -->

        <?php
        // Note: content uses normal WP editor (post_content) and title uses post_title
        echo '<p class="description">Title and main content are managed via the standard WordPress title and editor.</p>';

    }

    public function save_meta( $post_id, $post ) {
        // verify nonce
        if ( ! isset( $_POST['sa_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sa_meta_nonce'], 'sa_save_meta' ) ) {
            return;
        }
        error_log('save_meta triggered for post '.$post_id);

        // autosave / permissions
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( $post->post_type !== 'announcement' ) return;

        // ---- prevent infinite loop ----
        static $already_updating = false;
        if ( $already_updating ) return;
        $already_updating = true;

        // content (WYSIWYG)
        $content = $_POST['sa_content'] ?? '';
        $this->update_meta_if_present( $post_id, 'content', wp_kses_post($content) );

        $image_id = intval($_POST['sa_image'] ?? 0);
        update_post_meta($post_id, $this->meta_keys['image'], $image_id);

        // simple text fields
        $this->update_meta_if_present( $post_id, 'start_date', sanitize_text_field( $_POST['sa_start_date'] ?? '' ) );
        $this->update_meta_if_present( $post_id, 'end_date', sanitize_text_field( $_POST['sa_end_date'] ?? '' ) );
        $this->update_meta_if_present( $post_id, 'start_time', sanitize_text_field( $_POST['sa_start_time'] ?? '' ) );
        $this->update_meta_if_present( $post_id, 'end_time', sanitize_text_field( $_POST['sa_end_time'] ?? '' ) );
        $this->update_meta_if_present( $post_id, 'sub_title', sanitize_text_field( $_POST['sa_sub_title'] ?? '' ) );
        $this->update_meta_if_present( $post_id, 'location', sanitize_text_field( $_POST['sa_location'] ?? '' ) );
        $this->update_meta_if_present( $post_id, 'location_pin', sanitize_text_field( $_POST['sa_location_pin'] ?? '' ) );

        // contacts: parallel arrays
        $contacts = [];
        if ( isset( $_POST['sa_contacts_name'] ) && is_array( $_POST['sa_contacts_name'] ) ) {
            $names  = $_POST['sa_contacts_name'];
            $phones = $_POST['sa_contacts_phone'] ?? [];
            for ( $i = 0; $i < count($names); $i++ ) {
                $name = trim( sanitize_text_field($names[$i]) );
                if ( $name === '' ) continue;
                $phone = isset($phones[$i]) ? trim( sanitize_text_field($phones[$i])) : '';
                $contacts[] = [
                    'name'  => $name,
                    'phone' => $phone
                ];
            }
        }
        update_post_meta($post_id, $this->meta_keys['contacts'], $contacts);

        // price label
        $this->update_meta_if_present($post_id, 'price_label', sanitize_text_field($_POST['sa_price_label'] ?? 'Investasi'));

        // prices repeater
        $prices = [];
        if(isset($_POST['sa_prices_name']) && is_array($_POST['sa_prices_name'])) {
            $names  = $_POST['sa_prices_name'];
            $values = $_POST['sa_prices_value'] ?? [];
            for($i=0; $i<count($names); $i++){
                $name  = trim(sanitize_text_field($names[$i]));
                $value = isset($values[$i]) ? floatval($values[$i]) : 0;
                if($name === '') continue; // skip empty names
                $prices[] = ['name' => $name, 'value' => $value];
            }
        }
        update_post_meta($post_id, $this->meta_keys['prices'], $prices);


        // speakers: read parallel arrays and build array of speaker objects
        $speakers = [];
        if ( isset( $_POST['sa_speakers_name'] ) && is_array( $_POST['sa_speakers_name'] ) ) {
            $names = $_POST['sa_speakers_name'];
            $profs = $_POST['sa_speakers_prof'] ?? [];
            $orgs  = $_POST['sa_speakers_org'] ?? [];
            for ( $i = 0; $i < count( $names ); $i++ ) {
                $name = trim( sanitize_text_field( $names[ $i ] ) );
                if ( $name === '' ) continue;
                $profession = isset( $profs[ $i ] ) ? trim( sanitize_text_field( $profs[ $i ] ) ) : '';
                $organization = isset( $orgs[ $i ] ) ? trim( sanitize_text_field( $orgs[ $i ] ) ) : '';
                $speakers[] = [
                    'name' => $name,
                    'profession' => $profession,
                    'organization' => $organization
                ];
            }
        }
        update_post_meta( $post_id, $this->meta_keys['speakers'], $speakers );

        // organizers: parallel arrays
        $organizers = [];
        if ( isset( $_POST['sa_organizers_name'] ) && is_array( $_POST['sa_organizers_name'] ) ) {
            $names = $_POST['sa_organizers_name'];
            $descs = $_POST['sa_organizers_desc'] ?? [];
            for ( $i = 0; $i < count( $names ); $i++ ) {
                $name = trim( sanitize_text_field( $names[ $i ] ) );
                if ( $name === '' ) continue;
                $desc = isset( $descs[ $i ] ) ? trim( sanitize_text_field( $descs[ $i ] ) ) : '';
                $organizers[] = [
                    'name' => $name,
                    'description' => $desc
                ];
            }
        }
        update_post_meta( $post_id, $this->meta_keys['organizers'], $organizers );
    }

    private function update_meta_if_present( $post_id, $short_key, $value ) {
        if ( $value === '' ) {
            delete_post_meta( $post_id, $this->meta_keys[ $short_key ] );
        } else {
            update_post_meta( $post_id, $this->meta_keys[ $short_key ], $value );
        }
    }
}

<?php
/**
 * Booking Calendar Public Functions
 * Developed by Archeus Catalyst
 */

if (!defined('ABSPATH')) {
    exit;
}

class Booking_Calendar_Public {

    public function __construct() {
        add_shortcode('archeus_booking_calendar', array($this, 'render_calendar'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_calendar_scripts'));
        add_action('wp_ajax_get_calendar_data', array($this, 'handle_get_calendar_data'));
        add_action('wp_ajax_nopriv_get_calendar_data', array($this, 'handle_get_calendar_data'));
        // Add Elementor support
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_elementor_calendar_scripts'));
        add_action('elementor/frontend/before_enqueue_scripts', array($this, 'enqueue_elementor_calendar_scripts'));
    }

    /**
     * Enqueue public calendar scripts
     */
    public function enqueue_public_calendar_scripts() {
        // Check if we need to enqueue scripts in various contexts
        $should_enqueue = false;

        // Check in normal post context
        if (has_shortcode(get_the_content(), 'archeus_booking_calendar')) {
            $should_enqueue = true;
        }

        // Check in Elementor editor context
        if (defined('ELEMENTOR_VERSION') && (
            (isset($_GET['action']) && $_GET['action'] === 'elementor') ||
            (isset($_GET['elementor-preview']) && $_GET['elementor-preview'] > 0) ||
            (isset($_GET['elementor-mode']) && $_GET['elementor-mode'] === 'preview')
        )) {
            $should_enqueue = true;
        }

        // Check in WordPress editor context
        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen) {
                // Check if we're in post/page editor or block editor
                if (in_array($screen->base, array('post', 'page')) ||
                    $screen->base === 'widgets' ||
                    (method_exists($screen, 'is_block_editor') && $screen->is_block_editor())) {
                    $should_enqueue = true;
                }
            }
        }

        // Always enqueue for preview contexts
        if (isset($_GET['preview']) || isset($_GET['preview_id'])) {
            $should_enqueue = true;
        }

        // Enqueue scripts if needed
        if ($should_enqueue) {
            wp_enqueue_style('booking-calendar-css', ARCHEUS_BOOKING_URL . 'assets/css/calendar.css', array(), ARCHEUS_BOOKING_VERSION);
            wp_enqueue_script('booking-calendar-js', ARCHEUS_BOOKING_URL . 'assets/js/calendar.js', array('jquery'), ARCHEUS_BOOKING_VERSION, true);

            $booking_calendar = new Booking_Calendar();
            wp_localize_script('booking-calendar-js', 'calendar_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('calendar_nonce'),
                'max_months' => $booking_calendar->get_max_months_display()
            ));
        }
    }

    /**
     * Render calendar
     */
    public function render_calendar($atts) {
        $atts = shortcode_atts(array(
            'month' => date('n'), // Current month
            'year' => date('Y'),  // Current year
            'show_legend' => 'true'
        ), $atts);

        $month = intval($atts['month']);
        $year = intval($atts['year']);
        $show_legend = ($atts['show_legend'] === 'true');

        $calendar_html = $this->generate_calendar($month, $year);

        ob_start();
        ?>
        <div class="booking-calendar-container">
            <div class="booking-calendar-header">
                <button class="calendar-nav-btn prev-month" data-month="<?php echo $month-1; ?>" data-year="<?php echo $month == 1 ? $year-1 : $year; ?>">&laquo; <?php echo esc_html__('Sebelumnya', 'archeus-booking'); ?></button>
                <h3 class="current-month"><?php echo date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
                <button class="calendar-nav-btn next-month" data-month="<?php echo $month+1; ?>" data-year="<?php echo $month == 12 ? $year+1 : $year; ?>"><?php echo esc_html__('Berikutnya', 'archeus-booking'); ?> &raquo;</button>
            </div>

            <div class="booking-calendar">
                <div class="calendar-weekdays">
                    <div><?php echo esc_html__('Min', 'archeus-booking'); ?></div>
                    <div><?php echo esc_html__('Sen', 'archeus-booking'); ?></div>
                    <div><?php echo esc_html__('Sel', 'archeus-booking'); ?></div>
                    <div><?php echo esc_html__('Rab', 'archeus-booking'); ?></div>
                    <div><?php echo esc_html__('Kam', 'archeus-booking'); ?></div>
                    <div><?php echo esc_html__('Jum', 'archeus-booking'); ?></div>
                    <div><?php echo esc_html__('Sab', 'archeus-booking'); ?></div>
                </div>
                <div class="calendar-days">
                    <?php echo $calendar_html; ?>
                </div>
            </div>

            <?php if ($show_legend): ?>
            <div class="calendar-legend">
                <h4><?php echo esc_html__('Keterangan', 'archeus-booking'); ?></h4>
                <ul>
                    <li><span class="legend-color available"></span> <?php echo esc_html__('Tersedia', 'archeus-booking'); ?></li>
                    <li><span class="legend-color unavailable"></span> <?php echo esc_html__('Tidak Tersedia', 'archeus-booking'); ?></li>
                    <li><span class="legend-color holiday"></span> <?php echo esc_html__('Libur', 'archeus-booking'); ?></li>
                    <li><span class="legend-color full"></span> <?php echo esc_html__('Penuh', 'archeus-booking'); ?></li>
                    <li><span class="legend-color limited"></span> <?php echo esc_html__('Tersedia Terbatas', 'archeus-booking'); ?></li>
                    <li><span class="legend-color selected-date"></span> <?php echo esc_html__('Tanggal Terpilih', 'archeus-booking'); ?></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate calendar HTML for a specific month
     */
    private function generate_calendar($month, $year) {
        $booking_calendar = new Booking_Calendar();
        $availability_data = $booking_calendar->get_month_availability($year, $month);

        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);

        $calendar_html = '';

        // Add empty cells for the days before the first day of the month
        for ($i = 0; $i < $day_of_week; $i++) {
            $calendar_html .= '<div class="calendar-day empty"></div>';
        }

        // Add cells for each day of the month
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $date_obj = new DateTime($date);
            $day_of_week_num = $date_obj->format('w');
            $current_date = date('Y-m-d');
            
            // Get availability for this day
            $day_availability = isset($availability_data[$date]) ? $availability_data[$date] : array(
                'date' => $date,
                'availability_status' => 'available',
                'daily_limit' => 5,
                'booked_count' => 0
            );

            // Determine class based on availability
            $classes = array('calendar-day');
            $status = $day_availability['availability_status'];
            $booked_count = $day_availability['booked_count'];
            $daily_limit = $day_availability['daily_limit'];

            // Determine availability status
            if ($status === 'unavailable') {
                $classes[] = 'unavailable';
                $status_class = $status;
            } elseif ($status === 'holiday') {
                $classes[] = 'holiday';
                $status_class = $status;
            } else {
                if ($booked_count >= $daily_limit) {
                    $status_class = 'full';
                    $classes[] = 'full';
                } elseif ($booked_count > 0) {
                    $status_class = 'limited';
                    $classes[] = 'limited';
                } else {
                    $status_class = 'available';
                    $classes[] = 'available';
                }
            }

            // Disable past dates
            if ($date < $current_date) {
                $classes[] = 'past';
                $status_class = 'past';
            }

            $classes_str = implode(' ', $classes);
            
            $day_label = $day;
            
            // Check if this date has any bookings
            if ($booked_count > 0) {
                $day_label = $day . '<span class="booking-count">(' . $booked_count . '/' . $daily_limit . ')</span>';
            }

            $calendar_html .= '<div class="' . $classes_str . '" data-date="' . $date . '" data-status="' . $status_class . '">';
            $calendar_html .= '<span class="day-number">' . $day_label . '</span>';
            $calendar_html .= '</div>';
        }

        return $calendar_html;
    }

    /**
     * Handle AJAX request to get calendar data
     */
    public function handle_get_calendar_data() {
        check_ajax_referer('calendar_nonce', 'nonce');

        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

        $booking_calendar = new Booking_Calendar();
        $availability_data = $booking_calendar->get_month_availability($year, $month);
        
        // Format the data for JavaScript
        $formatted_data = array();
        foreach ($availability_data as $date => $data) {
            $status = $data['availability_status'];
            $booked_count = $data['booked_count'];
            $daily_limit = $data['daily_limit'];
            
            // Determine display status
            if ($status === 'unavailable' || $status === 'holiday') {
                $display_status = $status;
            } elseif ($booked_count >= $daily_limit) {
                $display_status = 'full';
            } elseif ($booked_count > 0) {
                $display_status = 'limited';
            } else {
                $display_status = 'available';
            }
            
            // Check if date is in the past
            if ($date < date('Y-m-d')) {
                $display_status = 'past';
            }
            
            $formatted_data[$date] = array(
                'status' => $display_status,
                'booked_count' => $booked_count,
                'daily_limit' => $daily_limit
            );
        }

        wp_send_json_success($formatted_data);
    }

    /**
     * Enqueue scripts for Elementor editor and frontend (calendar specific)
     */
    public function enqueue_elementor_calendar_scripts() {
        // Enqueue calendar-specific scripts
        wp_enqueue_style('booking-calendar-css', ARCHEUS_BOOKING_URL . 'assets/css/calendar.css', array(), ARCHEUS_BOOKING_VERSION);
        wp_enqueue_script('booking-calendar-js', ARCHEUS_BOOKING_URL . 'assets/js/calendar.js', array('jquery'), ARCHEUS_BOOKING_VERSION, true);

        $booking_calendar = new Booking_Calendar();
        wp_localize_script('booking-calendar-js', 'calendar_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('calendar_nonce'),
            'max_months' => $booking_calendar->get_max_months_display()
        ));
    }
}

<?php
/**
 * Booking Shortcodes Class
 * Developed by Archeus Catalyst
 */

if (!defined('ABSPATH')) {
    exit;
}

class Booking_Shortcodes {

    public function __construct() {
        // Shortcodes disederhanakan: hanya booking flow dan (terpisah) calendar di kelas publik
        add_shortcode('archeus_booking', array($this, 'render_booking_flow'));
        // Catatan: AJAX 'get_calendar_data' ditangani oleh Booking_Calendar_Public.
        // Hindari pendaftaran ganda yang mengarah ke callback tak ditemukan/duplikat.
    }

    public function render_booking_form($atts) {
        $atts = shortcode_atts(array('id' => 1, 'show_title' => 'true', 'title_text' => ''), $atts);
        $form_id = intval($atts['id']);
        $show_title = ($atts['show_title'] === 'true' || $atts['show_title'] === true || $atts['show_title'] === '1');
        $booking_db = new Booking_Database();
        $form = $booking_db->get_form($form_id);
        if (!$form) { return '<p>' . __('Form not found.', 'archeus-booking') . '</p>'; }
        $form_fields = $form->fields ? maybe_unserialize($form->fields) : array();
        $title_text = !empty($atts['title_text']) ? $atts['title_text'] : $form->name;
        ob_start();
        ?>
        <div class="booking-form-container" data-form-id="<?php echo esc_attr($form_id); ?>">
            <?php if ($show_title): ?><h3 class="booking-form-title"><?php echo esc_html($title_text); ?></h3><?php endif; ?>
            <form id="booking-form-<?php echo esc_attr($form_id); ?>" class="booking-form" method="post" enctype="multipart/form-data">
                <?php foreach ($form_fields as $field_key => $field_data): ?>
                    <div class="form-group field-<?php echo esc_attr($field_key); ?>">
                        <label for="<?php echo esc_attr($field_key); ?>_<?php echo esc_attr($form_id); ?>">
                            <?php echo esc_html($field_data['label']); ?>
                            <?php if (!empty($field_data['required'])): ?><span class="required">*</span><?php endif; ?>
                        </label>
<?php
                        switch ($field_data['type']) {
                            case 'text': echo '<input type="text" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder']) . '">'; break;
                            case 'email': echo '<input type="email" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder']) . '">'; break;
                            case 'number': echo '<input type="number" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder']) . '">'; break;
                            case 'date': echo '<input type="date" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder']) . '">'; break;
                            case 'time': echo '<input type="time" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder']) . '">'; break;
                            case 'select':
                                echo '<select id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . '>';
                                echo '<option value="">' . esc_html(($field_data['placeholder'] ?? '') ?: '-- Pilih --') . '</option>';
                                if ($field_key === 'service_type') {
                                    $booking_db_inner = new Booking_Database();
                                    $services = $booking_db_inner->get_services();
                                    foreach ($services as $service) { if ($service->is_active) { echo '<option value="' . esc_attr($service->name) . '">' . esc_html($service->name) . '</option>'; } }
                                } elseif (!empty($field_data['options']) && is_array($field_data['options'])) {
                                    foreach ($field_data['options'] as $opt) { echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>'; }
                                }
                                echo '</select>';
                                break;
                            case 'textarea': echo '<textarea id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" rows="2" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder']) . '"></textarea>'; break;
                            case 'file':
                                echo '<input type="file" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . 
                                     (!empty($field_data['required']) ? 'required' : '') . '>';
                                break;
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
                <div class="form-group booking-submit-wrapper">
                    <button type="submit" class="booking-submit-btn"><?php _e('Submit', 'archeus-booking'); ?></button>
                </div>
            </form>
            <div id="booking-message-<?php echo esc_attr($form_id); ?>" class="booking-message"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('booking-form-<?php echo esc_attr($form_id); ?>');
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // Logic for form submission
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function render_services_list($atts) {
        // ... content of this function
    }

    public function render_booking_flow($atts) {
        // Lebih toleran: terima id dari beberapa kunci, dan fallback pintar jika kosong
        $atts = shortcode_atts(array('id' => ''), $atts);
        $flow_id = 0;
        // Ambil dari beberapa kemungkinan atribut
        if (isset($atts['id'])) { $flow_id = intval($atts['id']); }
        if ($flow_id <= 0 && isset($atts['flow'])) { $flow_id = intval($atts['flow']); }
        if ($flow_id <= 0 && isset($atts['flow_id'])) { $flow_id = intval($atts['flow_id']); }

        $db = new Booking_Database();
        // Jika id masih kosong/invalid, pilih default: id terkecil yang ada datanya, jika tidak ada, id terkecil
        if ($flow_id <= 0 && method_exists($db, 'get_booking_flows')) {
            $flows = (array)$db->get_booking_flows();
            if (!empty($flows)) {
                // urutkan id asc
                usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
                $flow_id = intval($flows[0]->id);
                if (method_exists($db, 'get_booking_counts')) {
                    foreach ($flows as $f) {
                        $counts = $db->get_booking_counts(intval($f->id));
                        if (is_array($counts) && intval($counts['total']) > 0) { $flow_id = intval($f->id); break; }
                    }
                }
            }
        }
        // Jika tetap kosong setelah fallback, tampilkan pesan panduan
        if ($flow_id <= 0) {
            return '<div class="booking-flow-container"><p>'
                . esc_html__('Mohon gunakan atribut id pada shortcode, contoh: [archeus_booking id="2"]', 'archeus-booking')
                . '</p></div>';
        }

        $flow = $db->get_booking_flow($flow_id);
        if (!$flow) {
            return '<p>' . __('Booking flow not found.', 'archeus-booking') . '</p>';
        }

        $sections = !empty($flow->sections) ? (is_string($flow->sections) ? json_decode($flow->sections, true) : $flow->sections) : (is_string($flow->steps) ? json_decode($flow->steps, true) : $flow->steps);
        if (!is_array($sections) || empty($sections)) {
            return '<p>' . __('No sections defined for this booking flow.', 'archeus-booking') . '</p>';
        }

        // Fetch active services for possible service selection in time slot section
        $services = $db->get_services();

        ob_start();
        ?>
        <div class="booking-flow-container" data-flow-id="<?php echo esc_attr($flow_id); ?>">
            <div class="booking-flow-header">
                <h3 class="booking-flow-title"><?php echo esc_html($flow->name); ?></h3>
                <?php if (!empty($flow->description)): ?>
                    <p class="booking-flow-description"><?php echo esc_html($flow->description); ?></p>
                <?php endif; ?>
                <?php // Progress indicators intentionally hidden to reduce clutter ?>
            </div>

            <div class="booking-flow-contents">
                <?php foreach ($sections as $index => $section):
                    $type = $section['type'] ?? '';
                    $is_active = $index === 0 ? ' active' : '';
                    $section_num = $index + 1;
                ?>
                    <div id="section-<?php echo esc_attr($section_num); ?>" class="flow-section<?php echo $is_active; ?>" data-type="<?php echo esc_attr($type); ?>">
                        <?php if ($type === 'calendar'): ?>
                            <div class="section-block section-calendar">
                                <?php 
                                    $section_title = !empty($section['section_name']) ? $section['section_name'] : (!empty($section['name']) ? $section['name'] : (!empty($section['label']) ? $section['label'] : ''));
                                    if (!empty($section_title)):
                                ?>
                                    <h2 class="section-title"><?php echo esc_html($section_title); ?></h2>
                                <?php endif; ?>
                                <?php if (!empty($section['section_description'])): ?><p class="section-description"><?php echo esc_html($section['section_description']); ?></p><?php endif; ?>
                                <?php
                                    $month = intval(date('n'));
                                    $year = intval(date('Y'));
                                    // Generate month grid using same logic as public calendar
                                    $booking_calendar_obj = new Booking_Calendar();
                                    $availability_data = $booking_calendar_obj->get_month_availability($year, $month);

                                    $first_day = mktime(0, 0, 0, $month, 1, $year);
                                    $days_in_month = date('t', $first_day);
                                    $day_of_week = date('w', $first_day);
                                    $calendar_days_html = '';

                                    // Empty cells before first day
                                    for ($i = 0; $i < $day_of_week; $i++) {
                                        $calendar_days_html .= '<div class="calendar-day empty"></div>';
                                    }

                                    $current_date = date('Y-m-d');
                                    for ($day = 1; $day <= $days_in_month; $day++) {
                                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                        $day_availability = isset($availability_data[$date]) ? $availability_data[$date] : array(
                                            'date' => $date,
                                            'availability_status' => 'available',
                                            'daily_limit' => 5,
                                            'booked_count' => 0
                                        );

                                        $classes = array('calendar-day');
                                        $status = $day_availability['availability_status'];
                                        $booked_count = $day_availability['booked_count'];
                                        $daily_limit = $day_availability['daily_limit'];

                                        if ($status === 'unavailable') {
                                            $status_class = $status;
                                            $classes[] = $status;
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

                                        if ($date < $current_date) {
                                            $classes[] = 'past';
                                            $status_class = 'past';
                                        }

                                        $classes_str = implode(' ', $classes);
                                        $day_label = $day;
                                        if ($booked_count > 0 && $date >= $current_date) {
                                            $day_label = $day . '<span class="booking-count">(' . $booked_count . '/' . $daily_limit . ')</span>';
                                        }

                                        $calendar_days_html .= '<div class="' . $classes_str . '" data-date="' . $date . '" data-status="' . $status_class . '">';
                                        $calendar_days_html .= '<span class="day-number">' . $day_label . '</span>';
                                        $calendar_days_html .= '</div>';
                                    }
                                ?>
                                <!-- <div class="booking-calendar-container"> -->
                                    <div>
                                    <div class="booking-calendar-header">
                                        <button class="calendar-nav-btn prev-month" data-month="<?php echo esc_attr($month-1); ?>" data-year="<?php echo esc_attr($month == 1 ? $year-1 : $year); ?>">&laquo; <?php echo esc_html__('', 'archeus-booking'); ?></button>
                                        <h3 class="current-month"><?php $names = array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'); echo esc_html($names[$month-1] . ' ' . $year); ?></h3>
                                        <button class="calendar-nav-btn next-month" data-month="<?php echo esc_attr($month+1); ?>" data-year="<?php echo esc_attr($month == 12 ? $year+1 : $year); ?>"><?php echo esc_html__('', 'archeus-booking'); ?> &raquo;</button>
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
                                        <div class="calendar-days" id="archeus_calendar_days">
                                            <?php echo $calendar_days_html; ?>
                                        </div>
                                    </div>
                                    <div class="calendar-legend">
                                        <h4><?php echo esc_html__('Keterangan', 'archeus-booking'); ?></h4>
                                        <ul>
                                            <li><span class="legend-color available"></span> <?php echo esc_html__('Tersedia', 'archeus-booking'); ?></li>
                                            <li><span class="legend-color unavailable"></span> <?php echo esc_html__('Tidak Tersedia', 'archeus-booking'); ?></li>
                                            <li><span class="legend-color full"></span> <?php echo esc_html__('Penuh', 'archeus-booking'); ?></li>
                                            <li><span class="legend-color limited"></span> <?php echo esc_html__('Tersedia Terbatas', 'archeus-booking'); ?></li>
                                            <li><span class="legend-color selected-date"></span> <?php echo esc_html__('Tanggal Terpilih', 'archeus-booking'); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                        <?php elseif ($type === 'services'): ?>
                            <div class="section-block">
                                <?php $section_title = !empty($section['section_name']) ? $section['section_name'] : (!empty($section['name']) ? $section['name'] : (!empty($section['label']) ? $section['label'] : __('Pilih layanan', 'archeus-booking'))); ?>
                                <h2 class="section-title"><?php echo esc_html($section_title); ?></h2>
                                <?php if (!empty($section['section_description'])): ?><p class="section-description"><?php echo esc_html($section['section_description']); ?></p><?php endif; ?>
                                <div class="services-grid" id="archeus_services_grid">
                                    <?php foreach ($services as $svc): if ($svc->is_active): ?>
                                        <label class="service-card" data-value="<?php echo esc_attr($svc->name); ?>">
                                            <input class="visually-hidden" type="radio" name="service_type" value="<?php echo esc_attr($svc->name); ?>">
                                            <h4><?php echo esc_html($svc->name); ?></h4>
                                            <div class="service-price">
                                                <?php
                                                    $price_val = isset($svc->price) ? (float)$svc->price : 0;
                                                    if ($price_val <= 0) {
                                                        echo esc_html(__('Gratis', 'archeus-booking'));
                                                    } else {
                                                        $formatted = 'Rp ' . number_format($price_val, 0, ',', '.');
                                                        echo esc_html($formatted);
                                                    }
                                                ?>
                                            </div>
                                            <p class="service-duration"><?php echo esc_html(sprintf(__('Durasi %d menit', 'archeus-booking'), (int)$svc->duration)); ?></p>
                                        </label>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>

                        <?php elseif ($type === 'time_slot'): ?>
                            <div class="section-block">
                                <?php $section_title = !empty($section['section_name']) ? $section['section_name'] : (!empty($section['name']) ? $section['name'] : (!empty($section['label']) ? $section['label'] : __('Pilih waktu', 'archeus-booking'))); ?>
                                <h2 class="section-title"><?php echo esc_html($section_title); ?></h2>
                                <?php if (!empty($section['section_description'])): ?><p class="section-description"><?php echo esc_html($section['section_description']); ?></p><?php endif; ?>
                                <div class="time-slots-list time-slots-container" id="archeus_time_slots"></div>
                            </div>
                            
                        <?php elseif ($type === 'form'): ?>
                            <?php 
                                $form_id = isset($section['form_id']) ? intval($section['form_id']) : 1;
                                $form = $db->get_form($form_id);
                                $form_fields = $form && $form->fields ? maybe_unserialize($form->fields) : array();
                            ?>
                            <div class="section-block">
                                <?php $section_title = !empty($section['section_name']) ? $section['section_name'] : (!empty($section['name']) ? $section['name'] : (!empty($section['label']) ? $section['label'] : __('', 'archeus-booking'))); ?>
                                <h2 class="section-title"><?php echo esc_html($section_title); ?></h2>
                                <?php if ($form): ?>
                                    <?php if (!empty($section['section_description'])): ?><p class="section-description"><?php echo esc_html($section['section_description']); ?></p><?php endif; ?>
                                    <div class="booking-form-fields widefat" data-form-id="<?php echo esc_attr($form_id); ?>">
                                        <?php foreach ($form_fields as $field_key => $field_data): ?>
                                            <div class="form-group field-<?php echo esc_attr($field_key); ?><?php echo in_array($field_data['type'], ['textarea', 'file']) ? ' full-width' : ''; ?>">
                                                <label for="<?php echo esc_attr($field_key); ?>_<?php echo esc_attr($form_id); ?>">
                                                    <?php echo esc_html($field_data['label']); ?>
                                                    <?php if (!empty($field_data['required'])): ?><span class="required">*</span><?php endif; ?>
                                                </label>
                                                <?php
                                                switch ($field_data['type']) {
                                                    case 'text': echo '<input type="text" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder'] ?? '') . '">'; break;
                                                    case 'email': echo '<input type="email" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder'] ?? '') . '">'; break;
                                                    case 'number': echo '<input type="number" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder'] ?? '') . '">'; break;
                                                    case 'date': echo '<input type="date" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder'] ?? '') . '">'; break;
                                                    case 'time': echo '<input type="time" id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder'] ?? '') . '">'; break;
                                                    case 'select':
                                                        echo '<select id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" class="ab-select ab-dropdown" ' . (!empty($field_data['required']) ? 'required' : '') . '>';
                                                        echo '<option value="">' . esc_html(($field_data['placeholder'] ?? '') ?: '-- Pilih --') . '</option>';
                                                        if ($field_key === 'service_type') {
                                                            $services_inner = $db->get_services();
                                                            foreach ($services_inner as $service) { if ($service->is_active) { echo '<option value="' . esc_attr($service->name) . '">' . esc_html($service->name) . '</option>'; } }
                                                        } elseif (!empty($field_data['options']) && is_array($field_data['options'])) {
                                                            foreach ($field_data['options'] as $opt) { echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>'; }
                                                        }
                                                        echo '</select>';
                                                        break;
                                                    case 'textarea': echo '<textarea id="' . esc_attr($field_key) . '_' . esc_attr($form_id) . '" name="' . esc_attr($field_key) . '" rows="2" ' . (!empty($field_data['required']) ? 'required' : '') . ' placeholder="' . esc_attr($field_data['placeholder'] ?? '') . '"></textarea>'; break;
                                                case 'file':
                                                        $input_id = esc_attr($field_key) . '_' . esc_attr($form_id);
                                                        echo '<div class="file-upload">';
                                                        echo '<input type="file" class="bf-file-input" id="' . $input_id . '" name="' . esc_attr($field_key) . '" ' . (!empty($field_data['required']) ? 'required' : '') . '>';
                                                        echo '<label for="' . $input_id . '" class="bf-file-btn">' . esc_html__('Pilih File', 'archeus-booking') . '</label>';
                                                        echo '<span class="bf-file-name">' . esc_html__('Belum ada file', 'archeus-booking') . '</span>';
                                                        echo '<button type="button" class="bf-file-clear" aria-label="' . esc_attr__('Hapus file', 'archeus-booking') . '" title="' . esc_attr__('Hapus', 'archeus-booking') . '"></button>';
                                                        echo '</div>';
                                                        break;
                                                }
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p><?php _e('Selected form not found.', 'archeus-booking'); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p><?php _e('Unsupported section type.', 'archeus-booking'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="booking-flow-actions">
                <button class="submit-booking-btn button button-primary"><?php _e('Submit Reservasi', 'archeus-booking'); ?></button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            // Set default date to today if none selected, so time slots can show
            (function ensureDefaultDate(){
                var sel = sessionStorage.getItem('archeus_selected_date');
                if (!sel){
                    var d = new Date();
                    var y = d.getFullYear();
                    var m = ('0' + (d.getMonth()+1)).slice(-2);
                    var day = ('0' + d.getDate()).slice(-2);
                    sessionStorage.setItem('archeus_selected_date', y + '-' + m + '-' + day);
                }
            })();
            // Calendar interactions: match admin/public calendar UI
            var currentView = {
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear()
            };

            function getMonthName(m) {
                var names = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                return names[m-1];
            }

            function pad(n){ return (n<10? '0':'') + n; }

            function renderDays(month, year, data){
                var firstDay = new Date(year, month-1, 1);
                var startWeekday = firstDay.getDay();
                var daysInMonth = new Date(year, month, 0).getDate();
                var todayStr = (new Date()).toISOString().slice(0,10);
                var html = '';
                for (var i=0;i<startWeekday;i++) html += '<div class="calendar-day empty"></div>';
                for (var d=1; d<=daysInMonth; d++){
                    var dateStr = year + '-' + pad(month) + '-' + pad(d);
                    var info = data[dateStr] || {status: 'available', booked_count: 0, daily_limit: 5};
                    var status = info.status;
                    var classes = ['calendar-day', status];
                    if (dateStr < todayStr) { classes.push('past'); status = 'past'; }
                    var label = ''+d;
                    if (info.booked_count && info.daily_limit && dateStr >= todayStr){
                        label += '<span class="booking-count">(' + info.booked_count + '/' + info.daily_limit + ')</span>';
                    }
                    html += '<div class="' + classes.join(' ') + '" data-date="' + dateStr + '" data-status="' + status + '">'
                         +  '<span class="day-number">' + label + '</span>'
                         +  '</div>';
                }
                $('#archeus_calendar_days').html(html);
                // Highlight previously selected if in view
                highlightSelected();
            }

            function highlightSelected(){
                var sel = sessionStorage.getItem('archeus_selected_date');
                if (!sel) return;
                $('#archeus_calendar_days .calendar-day').removeClass('selected-date');
                $('#archeus_calendar_days .calendar-day[data-date="' + sel + '"]').addClass('selected-date');
            }

            function loadCalendar(month, year){
                // Disable interactions while loading (match admin behavior)
                $('.booking-calendar').addClass('loading');
                $.ajax({
                    url: calendar_ajax.ajax_url,
                    type: 'POST',
                    dataType: 'text',
                    data: {
                        action: 'get_calendar_data',
                        nonce: calendar_ajax.nonce,
                        month: month,
                        year: year
                    },
                    success: function(resp){
                        try {
                            if (typeof resp === 'string'){
                                var firstBrace = resp.indexOf('{');
                                if (firstBrace > 0) resp = resp.slice(firstBrace);
                                resp = JSON.parse(resp);
                            }
                            if (resp && resp.success){
                                renderDays(month, year, resp.data || {});
                                $('.booking-calendar-header .current-month').text(getMonthName(month) + ' ' + year);
                                // Update nav buttons
                                var prevM = month-1, prevY = year, nextM = month+1, nextY = year;
                                if (prevM < 1){ prevM = 12; prevY--; }
                                if (nextM > 12){ nextM = 1; nextY++; }
                                $('.prev-month').attr('data-month', prevM).attr('data-year', prevY);
                                $('.next-month').attr('data-month', nextM).attr('data-year', nextY);
                                currentView.month = month; currentView.year = year;
                                $('.booking-calendar').removeClass('loading');
                            }
                        } catch (e) {
                            console.error('Calendar JSON parse error', e, resp);
                            $('.booking-calendar').removeClass('loading');
                        }
                    },
                    error: function(xhr, status, err){
                        console.error('Error loading calendar data', status, err, xhr && xhr.responseText);
                        $('.booking-calendar').removeClass('loading');
                    }
                });
            }

            // Day selection
            $(document).on('click', '#archeus_calendar_days .calendar-day', function(){
                var status = $(this).data('status');
                if (status === 'available' || status === 'limited'){
                    var date = $(this).data('date');
                    sessionStorage.setItem('archeus_selected_date', date);
                    $('#archeus_calendar_days .calendar-day').removeClass('selected-date');
                    $(this).addClass('selected-date');
                    // Clear previously selected time slot on date change
                    sessionStorage.removeItem('archeus_selected_time_slot');
                    $('#archeus_time_slots').empty();
                    // Always reload time slots on date change
                    loadTimeSlots();
                }
            });

            // Month navigation
            $(document).on('click', '.calendar-nav-btn', function(e){
                e.preventDefault();
                var m = parseInt($(this).attr('data-month'), 10);
                var y = parseInt($(this).attr('data-year'), 10);
                loadCalendar(m,y);
            });

            // Initialize highlight for preselected/default date, render calendar
            highlightSelected();
            loadCalendar(currentView.month, currentView.year);
            // Ensure time slots are visible immediately on first load
            if ($('#archeus_time_slots').length){
                loadTimeSlots();
            }
            // Service selection via cards
            function selectServiceCard($card){
                $('.service-card').removeClass('selected');
                $card.addClass('selected');
                var val = $card.attr('data-value');
                // set radio checked
                $card.find('input[name="service_type"]').prop('checked', true).trigger('change');
                // persist and load
                if (val) sessionStorage.setItem('archeus_selected_service', val);
            }

            $(document).on('click', '.service-card', function(){
                selectServiceCard($(this));
            });

            // When radio changes (e.g., via keyboard)
            $(document).on('change', 'input[name="service_type"]', function(){
                var val = $('input[name="service_type"]:checked').val() || '';
                if (val) {
                    sessionStorage.setItem('archeus_selected_service', val);
                } else {
                    sessionStorage.removeItem('archeus_selected_service');
                }
                // Do not reload time slots on service selection; time slots depend only on date
            });

            // Tidak ada tombol Next; time slots dimuat saat tanggal/layanan berubah
            function renderTimeSlotsFromServer(date, service){
                var $container = $('#archeus_time_slots');
                $container.removeClass('time-slots-list').addClass('time-slots-container');
                $container.html('<div class="loading">Memuat slot waktu...</div>');

                $.ajax({
                    url: calendar_ajax.ajax_url,
                    type: 'POST',
                    dataType: 'text',
                    data: { action: 'get_available_time_slots', date: date, nonce: (window.calendar_ajax && calendar_ajax.nonce) ? calendar_ajax.nonce : '' },
                    success: function(resp){
                        try {
                            if (typeof resp === 'string'){
                                var firstBrace = resp.indexOf('{');
                                if (firstBrace > 0) resp = resp.slice(firstBrace);
                                resp = JSON.parse(resp);
                            }
                        } catch(e){
                            console.error('Time slots JSON parse error', e, resp);
                            $container.html('<div class="error">Gagal memuat slot waktu.</div>');
                            return;
                        }

                        if (!resp || !resp.success || !Array.isArray(resp.data)){
                            $container.html('<div class="empty">Tidak ada slot waktu tersedia.</div>');
                            return;
                        }

                        var selected = sessionStorage.getItem('archeus_selected_time_slot') || '';
                        var html = '';
                        var foundSelected = false;
                        resp.data.forEach(function(slot){
                            var range = (slot.start_time || '') + '-' + (slot.end_time || '');
                            var parts = range.split('-');
                            var label = (parts[0] || '') + ' - ' + (parts[1] || '');
                            var isSel = (selected === range);
                            if (isSel) { foundSelected = true; }
                            html += '<label class="time-slot-card' + (isSel ? ' selected' : '') + '" data-range="' + range + '">'
                                 +  '<input class="visually-hidden" type="radio" name="time_slot" value="' + range + '"' + (isSel ? ' checked' : '') + '>'
                                 +  '<h5>' + label + '</h5>'
                                 +  '</label>';
                        });
                        if (!foundSelected && selected) {
                            html = '<div class="notice">Slot sebelumnya tidak tersedia, silakan pilih yang lain.</div>' + html;
                        }
                        if (html === '') { html = '<div class="empty">Tidak ada slot waktu tersedia.</div>'; }
                        $container.html(html);
                    },
                    error: function(xhr, status, err){
                        console.error('Error loading time slots', status, err, xhr && xhr.responseText);
                        $container.html('<div class="error">Gagal memuat slot waktu.</div>');
                    }
                });
            }

            $(document).on('click', '.time-slot-card', function(){
                $('.time-slot-card').removeClass('selected');
                $(this).addClass('selected');
                var range = $(this).attr('data-range');
                $(this).find('input[name="time_slot"]').prop('checked', true).trigger('change');
                if (range) sessionStorage.setItem('archeus_selected_time_slot', range);
            });


            // Persist selected time slot
            $(document).on('change', 'input[name="time_slot"]', function(){
                var val = $(this).val();
                sessionStorage.setItem('archeus_selected_time_slot', val);
            });

            function loadTimeSlots(){
                var date = sessionStorage.getItem('archeus_selected_date') || '';
                if (!date){
                    $('#archeus_time_slots').html('<div class="empty">Silakan pilih tanggal.</div>');
                    return;
                }
                // Service selection does not affect time slots
                renderTimeSlotsFromServer(date, '');
            }

            // Always start with no preselected service on load/refresh
            (function(){
                try { sessionStorage.removeItem('archeus_selected_service'); } catch(e) {}
                $('.service-card').removeClass('selected');
                $('input[name="service_type"]').prop('checked', false);
            })();
        });
        </script>
        <?php
        return ob_get_clean();
    }

    // ... other functions
}

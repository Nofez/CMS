<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) exit;

// ==========================================
// 1. СОЗДАНИЕ ТАБЛИЦ БАЗЫ ДАННЫХ
// ==========================================
// Функция создаст таблицы при инициализации (если их еще нет)
add_action('init', 'mhw_create_db_tables');

function mhw_create_db_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Таблица охотников (Профили)
    $table_hunters = $wpdb->prefix . 'mhw_hunters';
    // Таблица рынка (Продажа частей)
    $table_market = $wpdb->prefix . 'mhw_market';
    // Таблица доски заказов
    $table_bounties = $wpdb->prefix . 'mhw_bounties';

    // SQL-запросы для создания таблиц
    $sql = "
    CREATE TABLE $table_hunters (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        char_name varchar(100) NOT NULL,
        fav_weapon varchar(50) NOT NULL,
        completed_quests int(11) DEFAULT 0 NOT NULL,
        zenny bigint(20) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;

    CREATE TABLE $table_market (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        seller_id bigint(20) NOT NULL,
        monster_name varchar(100) NOT NULL,
        monster_part varchar(100) NOT NULL,
        price bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;

    CREATE TABLE $table_bounties (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        client_id bigint(20) NOT NULL,
        target_monster varchar(100) NOT NULL,
        reward bigint(20) NOT NULL,
        deadline date NOT NULL,
        rarity int(2) NOT NULL,
        status varchar(50) DEFAULT 'open' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// ==========================================
// 2. БАЗОВЫЕ СТИЛИ (CSS) ДЛЯ ФОРМ
// ==========================================
add_action('wp_head', 'mhw_inject_css');
function mhw_inject_css() {
    echo '
    <style>
        .mhw-container { background: #1a1a1a; color: #e0e0e0; padding: 20px; border-radius: 8px; border: 1px solid #444; font-family: sans-serif; max-width: 600px; margin-bottom: 20px; }
        .mhw-container h3 { color: #d4af37; margin-top: 0; border-bottom: 1px solid #444; padding-bottom: 10px; }
        .mhw-stats { background: #2a2a2a; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #d4af37; }
        .mhw-stats p { margin: 5px 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #bbb; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #555; background: #222; color: #fff; border-radius: 4px; box-sizing: border-box; }
        .form-row { display: flex; gap: 15px; }
        .form-row .half { flex: 1; }
        .mhw-btn { background: #8b0000; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-transform: uppercase; width: 100%; transition: background 0.3s; }
        .mhw-btn:hover { background: #a50000; }
        .mhw-success { background: #1b4d1b; color: #90ee90; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #2e8b57; }
        .mhw-alert { background: #4d1b1b; color: #ff9999; padding: 10px; border-radius: 4px; border: 1px solid #8b0000; }
    </style>';
}

// ==========================================
// 3. ШОРТКОД: ЛИЧНЫЙ КАБИНЕТ [mhw_profile]
// ==========================================
add_shortcode('mhw_profile', 'render_mhw_profile_form');
function render_mhw_profile_form() {
    if (!is_user_logged_in()) return '<div class="mhw-alert">Вы должны быть в Лагере (авторизованы), чтобы открыть Карту Гильдии.</div>';

    global $wpdb;
    $table_name = $wpdb->prefix . 'mhw_hunters';
    $current_user_id = get_current_user_id();
    $message = '';

    if (isset($_POST['save_mhw_profile']) && wp_verify_nonce($_POST['mhw_profile_nonce'], 'mhw_profile_action')) {
        $char_name  = sanitize_text_field($_POST['char_name']);
        $fav_weapon = sanitize_text_field($_POST['fav_weapon']);
        $zenny      = intval($_POST['zenny']);

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE user_id = %d", $current_user_id));
        $data = ['char_name' => $char_name, 'fav_weapon' => $fav_weapon, 'zenny' => $zenny, 'user_id' => $current_user_id];

        if ($existing) {
            $wpdb->update($table_name, $data, ['user_id' => $current_user_id]);
            $message = '<div class="mhw-success">Карта Гильдии обновлена!</div>';
        } else {
            $data['completed_quests'] = 0;
            $wpdb->insert($table_name, $data);
            $message = '<div class="mhw-success">Добро пожаловать в Исследовательскую комиссию!</div>';
        }
    }

    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $current_user_id));
    $weapons = ['Двуручный меч', 'Длинный меч', 'Меч и щит', 'Парные клинки', 'Молот', 'Охотничий рог', 'Копье', 'Ганлэнс', 'Выкидной топор', 'Силовой топор', 'Глефа насекомых', 'Легкое орудие', 'Тяжелое орудие', 'Лук'];

    ob_start();
    ?>
    <div class="mhw-container">
        <h3>Карта Гильдии</h3>
        <?php echo $message; ?>
        <div class="mhw-stats">
            <p><strong>Выполнено заказов:</strong> <?php echo esc_html($profile ? $profile->completed_quests : '0'); ?></p>
            <p><strong>Баланс:</strong> <?php echo esc_html($profile ? $profile->zenny : '0'); ?>z (Zenny)</p>
        </div>
        <form method="POST">
            <?php wp_nonce_field('mhw_profile_action', 'mhw_profile_nonce'); ?>
            <div class="form-group">
                <label>Имя охотника:</label>
                <input type="text" name="char_name" value="<?php echo esc_attr($profile ? $profile->char_name : ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Любимое оружие:</label>
                <select name="fav_weapon">
                    <?php foreach($weapons as $w): ?>
                        <option value="<?php echo $w; ?>" <?php selected($profile ? $profile->fav_weapon : '', $w); ?>><?php echo $w; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Добавить Zenny (Только для теста):</label>
                <input type="number" name="zenny" value="<?php echo esc_attr($profile ? $profile->zenny : 0); ?>">
            </div>
            <button type="submit" name="save_mhw_profile" class="mhw-btn">Сохранить профиль</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// ==========================================
// 4. ШОРТКОД: ПРОДАЖА [mhw_sell_parts]
// ==========================================
add_shortcode('mhw_sell_parts', 'render_mhw_sell_form');
function render_mhw_sell_form() {
    if (!is_user_logged_in()) return '<div class="mhw-alert">Авторизуйтесь для торговли.</div>';

    global $wpdb;
    $message = '';

    if (isset($_POST['sell_mhw_part']) && wp_verify_nonce($_POST['mhw_sell_nonce'], 'mhw_sell_action')) {
        $data = [
            'seller_id'    => get_current_user_id(),
            'monster_name' => sanitize_text_field($_POST['monster_name']),
            'monster_part' => sanitize_text_field($_POST['monster_part']),
            'price'        => intval($_POST['part_price'])
        ];

        $wpdb->insert($wpdb->prefix . 'mhw_market', $data);
        $message = '<div class="mhw-success">Товар выставлен на продажу!</div>';
    }

    $monsters = ['Doshaguma', 'Chatacabra', 'Balahara', 'Rey Dau', 'Rathalos'];
    $parts = ['Чешуя', 'Панцирь', 'Коготь', 'Клык', 'Хвост', 'Пластинка', 'Самоцвет'];

    ob_start();
    ?>
    <div class="mhw-container">
        <h3>Торговая площадь</h3>
        <?php echo $message; ?>
        <form method="POST">
            <?php wp_nonce_field('mhw_sell_action', 'mhw_sell_nonce'); ?>
            <div class="form-row">
                <div class="form-group half">
                    <label>Монстр:</label>
                    <select name="monster_name">
                        <?php foreach($monsters as $m) echo "<option value='$m'>$m</option>"; ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label>Часть тела:</label>
                    <select name="monster_part">
                        <?php foreach($parts as $p) echo "<option value='$p'>$p</option>"; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Цена (Zenny):</label>
                <input type="number" name="part_price" required min="1">
            </div>
            <button type="submit" name="sell_mhw_part" class="mhw-btn">Выставить на продажу</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// ==========================================
// 5. ШОРТКОД: ДОСКА ЗАКАЗОВ [mhw_order_board]
// ==========================================
add_shortcode('mhw_order_board', 'render_mhw_order_form');
function render_mhw_order_form() {
    if (!is_user_logged_in()) return '<div class="mhw-alert">Авторизуйтесь, чтобы подойти к Доске Заказов.</div>';

    global $wpdb;
    $message = '';

    if (isset($_POST['place_mhw_order']) && wp_verify_nonce($_POST['mhw_order_nonce'], 'mhw_order_action')) {
        $data = [
            'client_id'      => get_current_user_id(),
            'target_monster' => sanitize_text_field($_POST['target_monster']),
            'reward'         => intval($_POST['bounty_reward']),
            'deadline'       => sanitize_text_field($_POST['deadline']),
            'rarity'         => intval($_POST['quest_rarity']),
            'status'         => 'open'
        ];

        $wpdb->insert($wpdb->prefix . 'mhw_bounties', $data);
        $message = '<div class="mhw-success">Контракт прикреплен на доску!</div>';
    }

    $monsters = ['Doshaguma', 'Chatacabra', 'Balahara', 'Rey Dau', 'Rathalos'];

    ob_start();
    ?>
    <div class="mhw-container">
        <h3>Доска заказов</h3>
        <?php echo $message; ?>
        <form method="POST">
            <?php wp_nonce_field('mhw_order_action', 'mhw_order_nonce'); ?>
            <div class="form-group">
                <label>Цель (Монстр):</label>
                <select name="target_monster">
                    <?php foreach($monsters as $m) echo "<option value='$m'>$m</option>"; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group half">
                    <label>Награда (Zenny):</label>
                    <input type="number" name="bounty_reward" required min="100">
                </div>
                <div class="form-group half">
                    <label>Дедлайн (До какого числа):</label>
                    <input type="date" name="deadline" required>
                </div>
            </div>
            <div class="form-group">
                <label>Редкость заказа:</label>
                <select name="quest_rarity">
                    <option value="1">★ (1 Звезда)</option>
                    <option value="2">★★ (2 Звезды)</option>
                    <option value="3">★★★ (3 Звезды)</option>
                    <option value="4">★★★★ (4 Звезды)</option>
                    <option value="5">★★★★★ (5 Звезд)</option>
                </select>
            </div>
            <button type="submit" name="place_mhw_order" class="mhw-btn">Разместить заказ</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
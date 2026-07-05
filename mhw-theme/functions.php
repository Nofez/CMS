<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) exit;

// ==========================================
// 1. СОЗДАНИЕ ТАБЛИЦ БАЗЫ ДАННЫХ
// ==========================================
add_action('init', 'mhw_create_db_tables');
function mhw_create_db_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_hunters = $wpdb->prefix . 'mhw_hunters';
    $table_market = $wpdb->prefix . 'mhw_market';
    $table_bounties = $wpdb->prefix . 'mhw_bounties';

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
// 2. ГЛОБАЛЬНЫЕ ИГРОВЫЕ СТИЛИ И СКРИПТ ВКЛАДОК
// ==========================================
add_action('wp_head', 'mhw_inject_assets');
function mhw_inject_assets() {
    echo '
    <style>
        /* Общий фон для всего сайта и центрирование */
        body { 
            background-color: #121416 !important; 
            background-image: radial-gradient(#1e2226 1px, transparent 1px) !important;
            background-size: 20px 20px !important;
            color: #e0e0e0 !important;
            font-family: "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .mhw-master-wrapper {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        /* Заголовок хаба */
        .mhw-hub-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .mhw-hub-header h1 {
            color: #d4af37;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0 0 5px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
        }
        .mhw-hub-header p {
            color: #8a96a3;
            margin: 0;
            font-style: italic;
        }

        /* Контейнер вкладок */
        .mhw-tabs-nav {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid #3a4149;
            margin-bottom: 20px;
        }
        .mhw-tab-btn {
            background: #1c2024;
            color: #a0aab5;
            border: 1px solid #3a4149;
            border-bottom: none;
            padding: 12px 20px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.95rem;
            text-transform: uppercase;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            transition: all 0.2s ease;
        }
        .mhw-tab-btn:hover {
            background: #252b31;
            color: #fff;
        }
        .mhw-tab-btn.active {
            background: #8b0000;
            color: #fff;
            border-color: #8b0000;
            box-shadow: 0 -2px 10px rgba(139,0,0,0.5);
        }

        /* Содержимое вкладок */
        .mhw-tab-content {
            display: none;
            background: #1a1d21;
            border: 1px solid #333a42;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .mhw-tab-content.active {
            display: block;
        }

        /* Оформление форм */
        .mhw-tab-content h3 { 
            color: #d4af37; 
            margin-top: 0; 
            margin-bottom: 20px;
            border-bottom: 1px solid #333a42; 
            padding-bottom: 10px;
            text-transform: uppercase;
            font-size: 1.3rem;
        }
        .mhw-stats { 
            background: #111315; 
            padding: 15px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            border-left: 4px solid #d4af37; 
        }
        .mhw-stats p { margin: 5px 0; font-size: 1.05rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #a0aab5; font-size: 0.9rem; text-transform: uppercase; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #3a4149; 
            background: #0f1112; 
            color: #fff; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #d4af37;
            outline: none;
        }
        .form-row { display: flex; gap: 20px; }
        .form-row .half { flex: 1; }
        
        /* Кнопка отправки */
        .mhw-btn { 
            background: linear-gradient(180deg, #990000 0%, #700000 100%); 
            color: white; 
            padding: 14px 20px; 
            border: 1px solid #500; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 1rem;
            text-transform: uppercase; 
            width: 100%; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            transition: background 0.2s; 
        }
        .mhw-btn:hover { background: linear-gradient(180deg, #b30000 0%, #8b0000 100%); }
        
        /* Уведомления */
        .mhw-success { background: #14321a; color: #7fe092; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #22522b; }
        .mhw-alert { background: #3c1414; color: #e07f7f; padding: 25px; border-radius: 6px; border: 1px solid #612020; text-align: center; font-size: 1.1rem; }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tabs = document.querySelectorAll(".mhw-tab-btn");
            const contents = document.querySelectorAll(".mhw-tab-content");

            tabs.forEach(tab => {
                tab.addEventListener("click", function() {
                    const target = this.dataset.tab;

                    tabs.forEach(t => t.classList.remove("active"));
                    contents.forEach(c => c.classList.remove("active"));

                    this.classList.add("active");
                    document.getElementById(target).classList.add("active");
                    
                    // Сохраняем активную вкладку после перезагрузки формы
                    localStorage.setItem("mhw_active_tab", target);
                });
            });

            // Восстанавливаем вкладку после отправки формы
            const savedTab = localStorage.getItem("mhw_active_tab");
            if (savedTab && document.getElementById(savedTab)) {
                const activeBtn = document.querySelector(`[data-tab="${savedTab}"]`);
                if (activeBtn) {
                    tabs.forEach(t => t.classList.remove("active"));
                    contents.forEach(c => c.classList.remove("active"));
                    activeBtn.classList.add("active");
                    document.getElementById(savedTab).classList.add("active");
                }
            }
        });
    </script>
    ';
}

// ==========================================
// 3. ЕДИНЫЙ ШОРТКОД ХАБА: [mhw_hub]
// ==========================================
add_shortcode('mhw_hub', 'render_mhw_hub');
function render_mhw_hub() {
    if (!is_user_logged_in()) {
        return '<div class="mhw-master-wrapper"><div class="mhw-alert">Вы должны быть в Лагере (авторизованы), чтобы получить доступ к Гильдейскому Хабу Monster Hunter Wilds.</div></div>';
    }

    global $wpdb;
    $current_user_id = get_current_user_id();

    $profile_msg = '';
    $market_msg = '';
    $order_msg = '';

    // --- Обработка формы 1: Профиль ---
    if (isset($_POST['save_mhw_profile']) && wp_verify_nonce($_POST['mhw_profile_nonce'], 'mhw_profile_action')) {
        $char_name  = sanitize_text_field($_POST['char_name']);
        $fav_weapon = sanitize_text_field($_POST['fav_weapon']);
        $zenny      = intval($_POST['zenny']);

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}mhw_hunters WHERE user_id = %d", $current_user_id));
        $data = ['char_name' => $char_name, 'fav_weapon' => $fav_weapon, 'zenny' => $zenny, 'user_id' => $current_user_id];

        if ($existing) {
            $wpdb->update("{$wpdb->prefix}mhw_hunters}", $data, ['user_id' => $current_user_id]);
        } else {
            $data['completed_quests'] = 0;
            $wpdb->insert("{$wpdb->prefix}mhw_hunters}", $data);
        }
        $profile_msg = '<div class="mhw-success">Карта Гильдии успешно обновлена!</div>';
    }

    // --- Обработка формы 2: Рынок ---
    if (isset($_POST['sell_mhw_part']) && wp_verify_nonce($_POST['mhw_sell_nonce'], 'mhw_sell_action')) {
        $wpdb->insert("{$wpdb->prefix}mhw_market", [
                'seller_id'    => $current_user_id,
                'monster_name' => sanitize_text_field($_POST['monster_name']),
                'monster_part' => sanitize_text_field($_POST['monster_part']),
                'price'        => intval($_POST['part_price'])
        ]);
        $market_msg = '<div class="mhw-success">Материал успешно выставлен на Торговую Площадь!</div>';
    }

    // --- Обработка формы 3: Доска Заказов ---
    if (isset($_POST['place_mhw_order']) && wp_verify_nonce($_POST['mhw_order_nonce'], 'mhw_order_action')) {
        $wpdb->insert("{$wpdb->prefix}mhw_bounties", [
                'client_id'      => $current_user_id,
                'target_monster' => sanitize_text_field($_POST['target_monster']),
                'reward'         => intval($_POST['bounty_reward']),
                'deadline'       => sanitize_text_field($_POST['deadline']),
                'rarity'         => intval($_POST['quest_rarity']),
                'status'         => 'open'
        ]);
        $order_msg = '<div class="mhw-success">Контракт успешно опубликован на Доске Заказов!</div>';
    }

    // Получаем актуальные данные профиля
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mhw_hunters WHERE user_id = %d", $current_user_id));

    $weapons = ['Двуручный меч', 'Длинный меч', 'Меч и щит', 'Парные клинки', 'Молот', 'Охотничий рог', 'Копье', 'Ганлэнс', 'Выкидной топор', 'Силовой топор', 'Глефа насекомых', 'Легкое орудие', 'Тяжелое орудие', 'Лук'];
    $monsters = ['Doshaguma', 'Chatacabra', 'Balahara', 'Rey Dau', 'Rathalos', 'Arkveld'];
    $parts = ['Чешуя', 'Панцирь', 'Коготь', 'Клык', 'Хвост', 'Пластинка', 'Самоцвет'];

    ob_start();
    ?>
    <div class="mhw-master-wrapper">
        <div class="mhw-hub-header">
            <h1>Исследовательский Хаб</h1>
            <p>Monster Hunter Wilds — База Данных Гильдии</p>
        </div>

        <!-- Навигация по вкладкам -->
        <div class="mhw-tabs-nav">
            <button class="mhw-tab-btn active" data-tab="tab-profile">Карта Гильдии</button>
            <button class="mhw-tab-btn" data-tab="tab-market">Рынок Материалов</button>
            <button class="mhw-tab-btn" data-tab="tab-orders">Доска Заказов</button>
        </div>

        <!-- ВКЛАДКА 1: ПРОФИЛЬ -->
        <div id="tab-profile" class="mhw-tab-content active">
            <h3>Профиль Охотника</h3>
            <?php echo $profile_msg; ?>
            <div class="mhw-stats">
                <p><strong>Выполнено заказов:</strong> <?php echo esc_html($profile ? $profile->completed_quests : '0'); ?></p>
                <p><strong>Баланс:</strong> <?php echo esc_html($profile ? $profile->zenny : '0'); ?>z (Zenny)</p>
            </div>
            <form method="POST">
                <?php wp_nonce_field('mhw_profile_action', 'mhw_profile_nonce'); ?>
                <div class="form-group">
                    <label>Имя охотника (Никнейм):</label>
                    <input type="text" name="char_name" value="<?php echo esc_attr($profile ? $profile->char_name : ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Любимый тип оружия:</label>
                    <select name="fav_weapon">
                        <?php foreach($weapons as $w): ?>
                            <option value="<?php echo $w; ?>" <?php selected($profile ? $profile->fav_weapon : '', $w); ?>><?php echo $w; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Добавить Zenny (Для теста):</label>
                    <input type="number" name="zenny" value="<?php echo esc_attr($profile ? $profile->zenny : 0); ?>">
                </div>
                <button type="submit" name="save_mhw_profile" class="mhw-btn">Сохранить Карту</button>
            </form>
        </div>

        <!-- ВКЛАДКА 2: РЫНОК -->
        <div id="tab-market" class="mhw-tab-content">
            <h3>Продажа частей монстров</h3>
            <?php echo $market_msg; ?>
            <form method="POST">
                <?php wp_nonce_field('mhw_sell_action', 'mhw_sell_nonce'); ?>
                <div class="form-row">
                    <div class="form-group half">
                        <label>Выберите монстра:</label>
                        <select name="monster_name">
                            <?php foreach($monsters as $m) echo "<option value='$m'>$m</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group half">
                        <label>Добытая часть:</label>
                        <select name="monster_part">
                            <?php foreach($parts as $p) echo "<option value='$p'>$p</option>"; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Стоимость лота (Zenny):</label>
                    <input type="number" name="part_price" required min="1" placeholder="Введите цену...">
                </div>
                <button type="submit" name="sell_mhw_part" class="mhw-btn">Выставить на рынок</button>
            </form>
        </div>

        <!-- ВКЛАДКА 3: ЗАКАЗЫ -->
        <div id="tab-orders" class="mhw-tab-content">
            <h3>Разместить контракт на охоту</h3>
            <?php echo $order_msg; ?>
            <form method="POST">
                <?php wp_nonce_field('mhw_order_action', 'mhw_order_nonce'); ?>
                <div class="form-group">
                    <label>Целевой монстр (Крупная дичь):</label>
                    <select name="target_monster">
                        <?php foreach($monsters as $m) echo "<option value='$m'>$m</option>"; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group half">
                        <label>Награда за голову (Zenny):</label>
                        <input type="number" name="bounty_reward" required min="100" placeholder="Например: 5000">
                    </div>
                    <div class="form-group half">
                        <label>Срок выполнения (До даты):</label>
                        <input type="date" name="deadline" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Сложность / Редкость контракта:</label>
                    <select name="quest_rarity">
                        <option value="1">★ (Низкий ранг)</option>
                        <option value="2">★★</option>
                        <option value="3">★★★ (Высокий ранг)</option>
                        <option value="4">★★★★</option>
                        <option value="5">★★★★★ (Высшая угроза)</option>
                    </select>
                </div>
                <button type="submit" name="place_mhw_order" class="mhw-btn">Разместить контракт на доске</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
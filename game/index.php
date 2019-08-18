<?php
require_once('functions.php');

$pid = get_pid($ref);

if (is_null($pid)) {
    header("Location: ".$ref);
    exit;
}

$player_data = get_pid_info($pid);

$gid = $player_data['game_id'];
end_of_turn($gid);
$player_data = get_pid_info($pid);

$game_data = get_game_info($gid);

$players_data = get_game_players_info($gid);

$player_data['resources'] = json_decode($player_data['resources'], true);
foreach ($players_data as &$other_player_data) {
    $other_player_data['resources'] = json_decode($other_player_data['resources'], true);
}
unset($other_player_data);

$currencies = get_currencies();
$producers = get_producers();
calculate_cards_effect($currencies, $producers, $game_data);

calculate_changes($player_data, $producers, $currencies);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Battle of Incrementals</title>
        <link rel="shortcut icon" href="../gfx/icons/favicon.png" type="image/png">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="../fonts.css">
        <script src="index.js"></script>
        <?php
            if ($player_data['commit']) {
                echo '<meta http-equiv="refresh" content="30">';
            }
        ?>
    </head>
    <body>
        <div class="screen-grid">
            <div class="screen-grid-money-wrapper">
                <div class="screen-grid-money">
                    <img class="icon-50px" src="../gfx/icons/money.png">
                    <p id="money" class="screen-stat-money">
                    <?= floor_number(json_value($player_data['resources'], 'money')) ?>
                    </p>
                </div>
                <div class="screen-grid-vp-total">
                    <img class="icon-50px" src="../gfx/icons/vp.png">
                    <p id="vp" class="screen-stat-money">
                        <?= $player_data['vp'] ?>
                    </p>
                </div>
            </div>
            <div class="screen-grid-buildings">

            <?php
                foreach ($currencies as $currency) {
                    ?>
                    <div class="screen-grid-building">
                        <div class="screen-grid-resource">
                            <img class="icon-30px" src="../gfx/icons/<?=$currency['name']?>.png">
                            <p id="currency_<?=$currency['id']?>" class="screen-stat-resource">
                                <?= floor_number(json_value($player_data['resources'], 'currency_'.$currency['id'])) ?>
                            </p>
                            <a href="action.php?pid=<?=$pid?>&action=sell&cid=<?=$currency['id']?>" class="screen-sell-all <?php if ($player_data['commit']) echo "disabled"?>">
                                Sell all
                            </a>
                        </div>
                    <?php
                    ksort($producers[$currency['id']]);
                    foreach ($producers[$currency['id']] as $producer) {
                        ?>
                        <div class="screen-grid-producer">
                            <img class="icon-30px" style="grid-area: pic" src="../gfx/icons/<?=$currency['industry_name']?>/<?=$producer['pic_name']?>.png">
                            <p id="name_<?=$currency['id']?>_<?=$producer['level']?>" class="screen-stat-producer-name">
                                <?=$producer['name']?>
                            </p>
                            <p id="num_<?=$currency['id']?>_<?=$producer['level']?>" class="screen-stat-producer-num">
                            <?= json_value($player_data['resources'], 'producer_'.$currency['id'].'_'.$producer['level']) ?>
                            </p>
                            <?php
                            if (!is_null($producer['production_rate'])) {
                                ?>
                                <p id="prod_<?=$currency['id']?>_<?=$producer['level']?>" class="screen-stat-producer-produce">
                                +<?=get_building_production($player_data['resources'], $producer)?>
                                </p>
                                <img class="icon-20px" style="grid-area: produce-icon" src="../gfx/icons/<?php if (!$producer['derivative']) echo $currency['name']; else echo $currency['industry_name'].'/'.$producers[$currency['id']][$producer['level']-1]['pic_name'] ?>.png">
                                <?php
                            }
                            ?>   
                            <div class="screen-grid-producer-cost">
                                <?php
                                $can_afford = can_buy_building($player_data['resources'], $producer);
                                if ($player_data['commit']) $can_afford = false;
                                if ($producer['uses_zero_level']) {
                                    ?>
                                    <img class="icon-20px" src="../gfx/icons/<?=$currency['industry_name']?>/<?=$producers[$currency['id']][0]['pic_name']?>.png"> 
                                    <p id="cost_<?=$currency['id']?>_<?=$producer['level']?>l" class="screen-stat-producer-cost">1</p>
                                    <p class="screen-stat-producer-cost">+</p>
                                    <?php
                                }
                                ?>
                                <?php
                                if (!is_null($producer['prev_level_cost'])) {
                                    ?>
                                    <img class="icon-20px" src="../gfx/icons/<?=$currency['industry_name']?>/<?=$producers[$currency['id']][$producer['level']-1]['pic_name']?>.png"> 
                                    <p id="cost_<?=$currency['id']?>_<?=$producer['level']?>d" class="screen-stat-producer-cost"><?=$producer['prev_level_cost']?></p>
                                    <p class="screen-stat-producer-cost">+</p>
                                    <?php
                                }
                                $next_level_cost = get_building_money_cost($player_data['resources'], $producer);

                                ?>
                                <img class="icon-20px" src="../gfx/icons/<?=$currency['name']?>.png"> 
                                <p id="cost_<?=$currency['id']?>_<?=$producer['level']?>" class="screen-stat-producer-cost"><?=ceil_number($next_level_cost)?></p>
                                <p class="screen-stat-producer-cost">/</p>
                                <img class="icon-20px" src="../gfx/icons/money.png">
                                <p id="cost_<?=$currency['id']?>_<?=$producer['level']?>m" class="screen-stat-producer-cost"><?=ceil_number($next_level_cost * $producer['money_cost_mult'])?></p>
                            </div>
                            <a href="action.php?pid=<?=$pid?>&action=buy&cid=<?=$currency['id']?>&level=<?=$producer['level']?>" class="screen-grid-producer-buy <?php if (!$can_afford) echo 'disabled' ?>">
                                +1
                            </a>
                        </div>
                        <?php
                    }

                    if ($currency['victory_condition'] == 0) { // For last-level generators
                        ?>
                            <div class="screen-grid-vp">
                                <img class="icon-30px" style="grid-area: pic" src="../gfx/icons/vp.png">
                                <p class="screen-stat-producer-name">
                                    Victory Point
                                </p>
                                <div class="screen-grid-producer-cost">
                                    <p class="screen-stat-producer-cost">for each&nbsp;</p>
                                    <img class="icon-20px" src="../gfx/icons/<?=$currency['industry_name']?>/<?=$producers[$currency['id']][4]['pic_name']?>.png"> 
                                    <p class="screen-stat-producer-cost">1 bought</p>
                                </div>
                                <p id="vp_<?=$currency['id']?>" class="screen-stat-vp">
                                    <?=json_value($player_data['resources'], 'rvp_'.$currency['id'])?>
                                </p>
                            </div>
                        <?php
                    }

                    if ($currency['victory_condition'] == 1) { // For first-level generators
                        ?>
                            <div class="screen-grid-vp">
                                <img class="icon-30px" style="grid-area: pic" src="../gfx/icons/vp.png">
                                <p class="screen-stat-producer-name">
                                    Next Victory Point
                                </p>
                                <div class="screen-grid-producer-cost">
                                    <p class="screen-stat-producer-cost">at&nbsp;</p>
                                    <img class="icon-20px" src="../gfx/icons/<?=$currency['industry_name']?>/<?=$producers[$currency['id']][0]['pic_name']?>.png"> 
                                    <p class="screen-stat-producer-cost"><?=get_vp_threshold($player_data['resources'], $currency)?></p>
                                </div>
                                <p id="vp_<?=$currency['id']?>" class="screen-stat-vp">
                                    <?=json_value($player_data['resources'], 'rvp_'.$currency['id'])?>
                                </p>
                            </div>
                        <?php
                    }

                    if ($currency['victory_condition'] == 2) { // For money acquisition
                        ?>
                            <div class="screen-grid-vp">
                                <img class="icon-30px" style="grid-area: pic" src="../gfx/icons/vp.png">
                                <p class="screen-stat-producer-name">
                                    Victory Point
                                </p>
                                <div class="screen-grid-producer-cost">
                                    <p class="screen-stat-producer-cost">Earn&nbsp;</p>
                                    <img class="icon-20px" src="../gfx/icons/<?=$currency['name']?>.png"> 
                                    <p class="screen-stat-producer-cost"><?=get_vp_threshold($player_data['resources'], $currency) - json_value($player_data['resources'], 'vp_progress_'.$currency['id'])?> more to get</p>
                                </div>
                                <p id="vp_<?=$currency['id']?>" class="screen-stat-vp">
                                    <?=json_value($player_data['resources'], 'rvp_'.$currency['id'])?>
                                </p>
                            </div>
                        <?php
                    }


                    ?>
                    </div>
                    <?php
                }
            ?>
            </div>

            <div class="screen-grid-info-wrapper">
                <div class="screen-grid-info-round">
                    <p id="round_num" class="screen-stat-round-num">
                        Round <?=$game_data['turn']?><br>out of <?=$CONST['GAME_TURN_LIMIT']?>
                    </p>
                    <div class="screen-stat-round-icons">
                    <?php
                    for ($round_num = 1; $round_num <= $CONST['GAME_TURN_LIMIT']; $round_num += 1) {
                        ?>
                        <div class="screen-stat-round-icon <?php
                            if ($round_num < $game_data['turn']) echo "past";
                            if ($round_num == $game_data['turn']) echo "current";
                        ?>"></div>
                        <?php
                    }
                    ?>
                    </div>
                </div>
                <div class="screen-grid-info-clock">
                    <?php $time_left = $game_data['last_update_ts'] + $CONST['TURN_LENGTH'] - time() ?>
                    <p id="clock" class="screen-stat-clock <?php if ($time_left < $CONST['TURN_ALERT']) echo "alert"?>"><?=convert_to_time($time_left)?>
                    </p>
                </div>

                <div class="screen-grid-info-players">
                    <img class="icon-50px" style="grid-column: 2; grid-row: 1" src="../gfx/icons/vp.png">
                <?php
                    $currency_num = 0;
                    foreach ($currencies as $currency) {
                        $currency_num += 1;
                        ?>
                        <img class="icon-30px" style="grid-column: <?=$currency_num+2?>; grid-row: 1" src="../gfx/icons/<?=$currency['name']?>.png">
                        <?php
                    }

                    $player_num = 0;
                    foreach ($players_data as $data) {
                        $player_num += 1;

                        if ($data['internal_id'] == $player_data['internal_id']) {
                        ?>
                            <div id="player_me" class="screen-stat-player-me" style="grid-column: 1 / -1; grid-row: <?=$player_num+1?>">
                            </div>
                        <?php
                        }
                        ?>
                        <p id="player_<?=$player_num?>" class="screen-stat-player-name <?php if (!$data['commit']) echo "not-committed"; ?>" style="grid-column: 1; grid-row: <?=$player_num+1?>">
                            <?=$data['name']?>
                        </p>
                        <p id="player_vp_<?=$player_num?>" class="screen-stat-player-vp" style="grid-column: 2; grid-row: <?=$player_num+1?>">
                            <?=$data['vp']?>
                        </p>
                        <?php

                        $currency_num = 0;
                        foreach ($currencies as $currency) {
                            $currency_num += 1;
                            ?>
                            <p id="player_currency_<?=$player_num?>" class="screen-stat-player-currency" style="grid-column: <?=$currency_num+2?>; grid-row: <?=$player_num+1?>">
                                <?=floor(json_value($data['resources'], 'currency_'.$currency['id']))?><br>
                                <span class='screen-stat-player-currency-growth'>(+<?=get_currency_production($data['resources'], $producers[$currency['id']])?>)</span>
                            </p>
                            <?php
                        }
                    }
                ?>
                </div>
            </div>

            <div class="screen-grid-controls">
                <a href="action.php?pid=<?=$pid?>&action=commit" class="screen-stat-button <?php if ($player_data['commit']) echo "disabled"?>">Commit</a>
                <a href="action.php?pid=<?=$pid?>&action=start_over" class="screen-stat-button <?php if ($player_data['commit']) echo "disabled"?>">Start Over</a>
            </div>

            <div class="screen-grid-logs">
                <p class="screen-info-logs">Log</p>
                <?php
                    $log_db = perform_query('SELECT * FROM logs WHERE game_id = "'.$gid.'" ORDER BY id DESC');
                    while ($log = $log_db->fetch_assoc()) {
                        $log['data'] = json_decode($log['data'], true);
                        if ($log['type'] == 'card_played') {
                            $log_card_data = get_card_info($log['data']['what']);
                            ?>
                            <p class="screen-stat-log"><?=generate_header($log['turn'])?>: <span class='variable'><?=$log_card_data['name']?></span> came into play</p>
                            <?php
                        }

                        if ($log['type'] == 'building_lost') {
                            $log_player_data = get_player_info($log['data']['who']);
                            $log_building_data = get_building_info($log['data']['what']);
                            ?>
                            <p class="screen-stat-log"><?=generate_header($log['turn'])?>: <span class='variable'><?=$log_player_data['name']?></span> lost <span class='variable'><?=$log_building_data['name']?></span></p>
                            <?php
                        }

                        if ($log['type'] == 'building_none') {
                            $log_player_data = get_player_info($log['data']['who']);
                            ?>
                            <p class="screen-stat-log"><?=generate_header($log['turn'])?>: <span class='variable'><?=$log_player_data['name']?></span> has no building to destroy</p>
                            <?php
                        }

                        if ($log['type'] == 'building_gained') {
                            $log_player_data = get_player_info($log['data']['who']);
                            $log_building_data = get_building_info($log['data']['what']);
                            ?>
                            <p class="screen-stat-log"><?=generate_header($log['turn'])?>: <span class='variable'><?=$log_player_data['name']?></span> gained <span class='variable'><?=$log_building_data['name']?></span></p>
                            <?php
                        }

                        if ($log['type'] == 'vp_gained') {
                            $log_player_data = get_player_info($log['data']['who']);
                            $log_amount = $log['data']['amount'];
                            ?>
                            <p class="screen-stat-log"><?=generate_header($log['turn'])?>: <span class='variable'><?=$log_player_data['name']?></span> gained <span class='variable'><?=$log_amount?></span> VP</p>
                            <?php
                        }

                        if ($log['type'] == 'currencies_sold') {
                            $log_player_data = get_player_info($log['data']['who']);
                            $log_amount = $log['data']['amount'];
                            ?>
                            <p class="screen-stat-log"><?=generate_header($log['turn'])?>: <span class='variable'><?=$log_player_data['name']?></span> sold all currencies for <span class='variable'><?=floor_number($log_amount)?></span> money</p>
                            <?php
                        }

                        if ($log['type'] == 'turn_start') {
                            ?>
                            <p class="screen-stat-log" style="text-align: center">=== START OF ROUND <?=$log['turn']?> ===</p>
                            <?php
                        }
                    }
                ?>
            </div>

            <div class="screen-grid-curcards">
                <p class="screen-grid-curcards-green" style="grid-row: 1; grid-column: 1 / 6">End of round</p>
                <p class="screen-grid-curcards-red" style="grid-row: 1; grid-column: 6 / 11">Start of round</p>
                <?php
                for ($round = 1; $round <= $CONST['GAME_TURN_LIMIT']; $round += 1) {
                    ?>
                    <p class="screen-grid-curcards-<?=($round <= 5) ? 'green' : 'red'?>" style="grid-row: 2; grid-column: <?=$round?>"><?=$round?></p>
                    <?php
                }

                $log_db = perform_query('SELECT * FROM logs WHERE game_id = "'.$gid.'" AND type = "card_played" ORDER BY ts ASC');
                while ($log = $log_db->fetch_assoc()) {
                    $log['data'] = json_decode($log['data'], true);
                    $card_data = get_card_info($log['data']['what']);
                    ?>
                    <p class="screen-stat-curcard-name" span="grid-column: <?=$log['turn']?>"><?=$card_data['name']?></p>
                    <p class="screen-stat-curcard-desc" span="grid-column: <?=$log['turn']?>"><?=get_card_description($card_data, $currencies, $producers)?></p>
                    <?php
                }
                ?>
            </div> 

            <div class="screen-grid-cards">
            <?php
                if ($game_data['turn'] > 1 && $game_data['turn'] < 5) {
                    ?>
                    <div class="screen-grid-cards-locked">
                        <img src="../gfx/icons/lock.png" style="position: absolute; width: 80px; height: 80px; bottom: 50%; left: calc(50% - 40px)">
                        <p class="screen-stat-lock" style="position: absolute; top: 55%">New cards unlock in Round 5</p>
                    </div>
                    <?php
                }
                else if ($game_data['turn'] > 5) {
                    ?>
                    <div class="screen-grid-cards-locked">
                        <img src="../gfx/icons/lock.png" style="position: absolute; width: 120px; height: 120px; bottom: calc(50% - 60px); left: calc(50% - 60px)">
                    </div>
                    <?php
                }
                else {
                    $player_cards = explode(',', $player_data['cards']);
                    $cards_shown = 0;
                    foreach ($player_cards as $card) {
                        $card_info = get_card_info($card);
                        ?>
                        <div class="screen-grid-card">
                            <p class="screen-stat-card-name"><?=$card_info['name']?></p>
                            <p class="screen-stat-card-desc"><?=get_card_description($card_info, $currencies, $producers)?></p>
                        <?php
                        if ($card == $player_data['card_picked']) {
                            ?>
                            <p class="screen-stat-card-played">Played</p>
                            <?php
                        }
                        else if (!$player_data['commit']) {
                            ?>
                            <a href="action.php?pid=<?=$pid?>&action=play&card=<?=$cards_shown?>" class="screen-stat-card-play">Play card</a>
                            <?php
                        }
                        ?>
                        </div>
                        <?php
                        $cards_shown += 1;
                    }
                }
            ?>
            </div>
        </div>

        <script> ticking_clock(<?=$time_left?>, <?=$CONST['TURN_ALERT']?>); </script>
    </body>
</html>
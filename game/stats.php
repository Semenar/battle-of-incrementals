<?php
require_once("functions.php");

$gid = NULL;
if (isset($_GET['game'])) $gid = $_GET['game'];
if (!is_null($gid) && is_null(get_game_info($gid))) $gid = NULL;

if (is_null($gid)) {
    header("Location: ../index.php");
    exit;
}

$game_data = get_game_info($gid);
$players_data = get_game_players_info($gid);

foreach ($players_data as &$other_player_data) {
    $other_player_data['resources'] = json_decode($other_player_data['resources'], true);
}
unset($other_player_data);

$producers = get_producers();
$currencies = get_currencies();
calculate_cards_effect($currencies, $producers, $game_data);
?>

<!DOCTYPE html>
    <head>
        <title>Battle of Incrementals</title>
        <link rel="shortcut icon" href="../gfx/icons/favicon.png" type="image/png">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="../fonts.css">
    </head>
    <body>
        <p class='stats-results'>Results of Game #<?=$gid?></p>
        <div class='stats-info-players'>
            <div class='stats-gold-ribbon'></div>
            <div class='stats-silver-ribbon'></div>
            <div class='stats-bronze-ribbon'></div>
            <div class='stats-4th-ribbon'></div>
            <div class='stats-5th-ribbon'></div>
            <div class='stats-icon-ribbon'></div>
            <div class='stats-vp-ribbon'></div>

            <img class='stats-icon-80px' src='../gfx/icons/vp.png' style='grid-row: 1; grid-column: 3'>
            <?php
                $currency_num = 1;
                foreach ($currencies as $currency) {
                    ?>
                    <img class='stats-icon-50px' src='../gfx/icons/<?=$currency['name']?>.png' style='grid-row: 1; grid-column: <?=$currency_num+3?>'>
                    <?php
                    $currency_num += 1;
                }

                $place = 1;
                foreach ($players_data as $player) {
                    ?>
                    <p class='stats-place' style='grid-row: <?=$place+1?>'><?=$place?></p>
                    <p class='stats-name' style='grid-row: <?=$place+1?>'><?=$player['name']?></p>
                    <p class='stats-vp' style='grid-row: <?=$place+1?>'><?=$player['vp']?></p>
                    <?php
                    $currency_num = 1;
                    foreach ($currencies as $currency) {
                        ?>
                        <p class='stats-currency' style='grid-row: <?=$place+1?>; grid-column: <?=$currency_num+3?>'>
                        <?=floor(json_value($player['resources'], 'currency_'.$currency['id']))?> <span class='stats-currency-small'>(+<?=get_currency_production($player['resources'], $producers[$currency['id']])?>)</span><br>
                        <?=json_value($player['resources'], 'rvp_'.$currency['id'])?> VP
                        </p>
                        <?php
                        $currency_num += 1;
                    }
                    $place += 1;
                }
            ?>
        </div>
    </body>
</html>
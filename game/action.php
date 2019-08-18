<?php
require_once('functions.php');

$pid = get_pid($ref);
if (!is_null($pid) && isset($_GET['action'])) {
    $player_data = get_pid_info($pid);

    if (!$player_data['commit']) {
        $player_data['resources'] = json_decode($player_data['resources'], true);
        $currencies = get_currencies();
        $producers = get_producers();
        calculate_changes($player_data, $producers, $currencies);

        $action = $_GET['action'];

        if ($action == 'sell' && isset($_GET['cid'])) {
            $cid = $_GET['cid'];

            // Check that cid exists
            if (array_key_exists($cid, $currencies)) {
                perform_query("INSERT INTO actions (player_id, currency) VALUES (".$player_data['internal_id'].", ".$cid.")");
            }
        }

        if ($action == 'buy' && isset($_GET['cid']) && isset($_GET['level'])) {
            $cid = $_GET['cid'];
            $level = $_GET['level'];

            // Check that cid and level exist
            if (array_key_exists($cid, $currencies) && array_key_exists($level, $producers[$cid])) {     
                // Check that you can buy it
                if (can_buy_building($player_data['resources'], $producers[$cid][$level]))
                    perform_query("INSERT INTO actions (player_id, currency, level) VALUES (".$player_data['internal_id'].", ".$cid.", ".$level.")");
            }
        }

        if ($action == 'play' && isset($_GET['card'])) {
            $card_num = $_GET['card'];
            $cards = explode(',', $player_data['cards']);

            // Check that card exists
            if (array_key_exists($card_num, $cards)) {
                perform_query("UPDATE players SET card_picked = ".$cards[$card_num]." WHERE external_id = '".$pid."'");
            }
        }

        if ($action == 'commit') {
            perform_query("UPDATE players SET commit = 1 WHERE external_id = '".$pid."'");
        }

        if ($action == 'start_over') {
            perform_query("DELETE FROM actions WHERE player_id = ".$player_data['internal_id']);
            perform_query("UPDATE players SET card_picked = NULL WHERE external_id = '".$pid."'");
        }
    }

    header("Location: index.php?pid=".$pid);
}
else header("Location: index.php");
?>
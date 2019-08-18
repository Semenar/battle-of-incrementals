<?php
require_once('../globals.php');

$CONST = array();
$CONST['GAME_TURN_LIMIT'] = 10;
$CONST['TURN_LENGTH'] = 3 * 86400;
$CONST['TURN_ALERT'] = 3600;
$CONST['CARD_CHOICES'] = 3;
$CONST['EPS'] = 1e-6;

function perform_query(string $db_query) {
    global $db_host, $db_address, $db_user, $db_password;

    $db_connection = mysqli_connect($db_host, $db_user, $db_password, $db_address);
    if ($db_connection) {
        $db_connection->query("SET NAMES 'utf8'");
        $db_connection->query("SET CHARACTER SET 'utf8'");

        $db_result = $db_connection->query($db_query);
        if ($db_result) {
            return $db_result;
        }
        else return NULL;
    }
    else return NULL;
}

// Technical

function get_pid(&$ref) {
    global $_GET, $_COOKIE, $CONST;

    $ref = "../index.php";
    $pid = NULL;
    // Found one in $_GET? So it is!
    if (is_null($pid) && array_key_exists('pid', $_GET)) $pid = $_GET['pid'];
    
    // Found one in $_COOKIE? Let's test that!
    if (is_null($pid) && array_key_exists('pid', $_COOKIE)) $pid = $_COOKIE['pid'];

    // Verify pid by checking it in playerbase
    if (!is_null($pid)) {
        $player_data = get_pid_info($pid);
        if (is_null($player_data)) $pid = NULL;
        else {
            // Verify game number (that is, that it is still active)
            $game_id = $player_data['game_id'];
            $game_data = get_game_info($game_id);
            if (is_null($game_data)) $pid = NULL;
            if ($game_data['turn'] > $CONST['GAME_TURN_LIMIT']) {
                $ref = "stats.php?game=".$game_id;
                $pid = NULL;
            }
        }
    }

    if (is_null($pid)) setcookie("pid", "", 1);
    else setcookie("pid", $pid, 0x7FFFFFFF);

    return $pid;
}

function generate_header($turn) {
    if ($turn <= 5) return "End of round ".$turn;
    else return "Start of round ".$turn;
}

function get_card_description($card, $currencies, $producers) {
    if ($card['type'] == 0) {
        return "All players with the least <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> production lose random building.";
    }
    if ($card['type'] == 1) {
        return "All players with the most <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> production get two <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['industry_name']."/".$producers[$card['data']['industry']][0]['pic_name'].".png'>.";
    }
    if ($card['type'] == 2) {
        return "All players lose all banked <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'>.";
    }
    if ($card['type'] == 3) {
        return "All players gain <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> 3.";
    }
    if ($card['type'] == 4) {
        return "All players' <img class='icon-text' src='../gfx/icons/money.png'> are doubled.";
    }
    if ($card['type'] == 5) {
        return "All <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> building costs are reset for all players.";
    }
    if ($card['type'] == 6) {
        return "<img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['industry_name']."/".$producers[$card['data']['industry']][0]['pic_name'].".png'> produce <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> 1 more for all players.";
    }

    if ($card['type'] == 7) {
        return "All players with the most <img class='icon-text' src='../gfx/icons/money.png'> gain 3 <img class='icon-text' src='../gfx/icons/vp.png'>.";
    }
    if ($card['type'] == 8) {
        return "All players with the most <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> production gain 2 <img class='icon-text' src='../gfx/icons/vp.png'>.";
    }
    if ($card['type'] == 9) {
        return "Everything related to <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> is reset. Players retain <img class='icon-text' src='../gfx/icons/vp.png'> earned through <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'>.<br>All players gain <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> 3.";
    }
    if ($card['type'] == 10) {
        return "All future <img class='icon-text' src='../gfx/icons/vp.png'> income from <img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> is doubled for all players.";
    }
    if ($card['type'] == 11) {
        return "All cards' permanent effects from previous rounds are disabled.";
    }
    if ($card['type'] == 12) {
        return "All players with the most <img class='icon-text' src='../gfx/icons/vp.png'> sell all their currencies for <img class='icon-text' src='../gfx/icons/money.png'>.";
    }
    if ($card['type'] == 13) {
        return "<img class='icon-text' src='../gfx/icons/".$currencies[$card['data']['industry']]['name'].".png'> buildings do not produce anything in the end of this round.";
    }
}

// DB connection

function get_player_info($internal_id) {
    $query = perform_query("SELECT * FROM players WHERE internal_id = '$internal_id'");
    if (is_null($query)) return NULL;
    $data = $query->fetch_assoc();
    $query->free();
    return $data;
}

function get_pid_info($pid) {
    $query = perform_query("SELECT * FROM players WHERE external_id = '$pid'");
    if (is_null($query)) return NULL;
    $data = $query->fetch_assoc();
    $query->free();
    return $data;
}

function get_game_info($gid) {
    $query = perform_query("SELECT * FROM games WHERE id = '$gid'");
    if (is_null($query)) return NULL;
    $data = $query->fetch_assoc();
    $query->free();

    // Fixes
    $data['last_update_ts'] = date_create($data['last_update_ts'])->getTimestamp();

    return $data;
}

function get_game_players_info($gid) {
    $query = perform_query("SELECT * FROM players WHERE game_id = '$gid' ORDER BY vp DESC");
    if (is_null($query)) return NULL;
    $data = $query->fetch_all(MYSQLI_ASSOC);
    $query->free();
    return $data;
}

function get_changes_made($pid) {
    $query = perform_query("SELECT * FROM actions WHERE player_id = '$pid'");
    if (is_null($query)) return NULL;
    $data = $query->fetch_all(MYSQLI_ASSOC);
    $query->free();
    return $data;
}

function get_building_info($building_id) {
    $query = perform_query("SELECT * FROM producers WHERE id = '$building_id'");
    if (is_null($query)) return NULL;
    $data = $query->fetch_assoc();
    $query->free();
    return $data;
}

function get_card_info($card_id) {
    $query = perform_query("SELECT * FROM cards WHERE id = '$card_id'");
    if (is_null($query)) return NULL;
    $data = $query->fetch_assoc();
    $query->free();

    // Fixes
    $data['data'] = json_decode($data['data'], true);

    return $data;
}

function get_currencies() {
    $currencies = array();

    $query = perform_query("SELECT * FROM currencies");
    if (is_null($query)) return NULL;
    while ($currency = $query->fetch_assoc()) {
        $currency['vp_per_level'] = 1;
        $currencies[$currency['id']] = $currency;
    }
    $query->free();

    return $currencies;
}

function get_producers() {
    $producers = array();

    $query = perform_query("SELECT * FROM producers");
    if (is_null($query)) return NULL;
    while ($producer = $query->fetch_assoc()) {
        if (!array_key_exists($producer['currency_id'], $producers)) $producers[$producer['currency_id']] = array();
        $producers[$producer['currency_id']][$producer['level']] = $producer;
    }

    return $producers;
}

// Utilities

function json_value($json, $key) {
    if (array_key_exists($key, $json)) return $json[$key];
    else return 0;
}

function ceil_number($number, $digits=2) {
    return ceil($number * pow(10, $digits)) / pow(10, $digits);
}

function floor_number($number, $digits=2) {
    return floor($number * pow(10, $digits)) / pow(10, $digits);
}

function convert_to_time($seconds) {
    if ($seconds < 0) return "00:00";

    $minutes = intdiv($seconds, 60);
    $seconds -= $minutes * 60;

    $hours = intdiv($minutes, 60);
    $minutes -= $hours * 60;

    $days = intdiv($hours, 24);
    $hours -= $days * 24;

    if ($days > 0) {
        return $days.'.'.str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
    else if ($hours > 0) {
        return str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
    else {
        return str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
}

// Production

function get_building_production($resources, $producer) {
    if (is_null($producer['production_rate'])) return 0;

    $production = $producer['production_rate']; // base
    $production += $producer['production_increase'] * json_value($resources, 'bought_'.$producer['currency_id'].'_'.$producer['level']);

    return $production;
}

function get_building_money_cost($resources, $producer) {
    $cost = $producer['base_cost']; // base
    $cost *= pow($producer['cost_mult'], json_value($resources, 'bought_'.$producer['currency_id'].'_'.$producer['level']));

    return $cost;
}

function can_buy_building($resources, $producer) {
    $cid = $producer['currency_id'];
    $lid = $producer['level'];

    $can_buy = true;
    if ($producer['uses_zero_level'] && json_value($resources, 'producer_'.$cid.'_0') < 1) $can_buy = false;
    if (!is_null($producer['prev_level_cost']) && json_value($resources, 'producer_'.$cid.'_'.($lid-1)) < $producer['prev_level_cost']) $can_buy = false;
    if (json_value($resources, 'money') + $producer['money_cost_mult'] * json_value($resources, 'currency_'.$cid) < $producer['money_cost_mult'] * get_building_money_cost($resources, $producer)) $can_buy = false;

    return $can_buy;
}

function get_currency_production($resources, $producers) {
    $overall_production = 0;

    foreach ($producers as $producer) {
        if (!$producer['derivative']) {
            $overall_production += json_value($resources, 'producer_'.$producer['currency_id'].'_'.$producer['level']) * get_building_production($resources, $producer);
        }
    }

    return $overall_production;
}

function get_currency_production_proper($resources, $producers) {
    $overall_production = 0;

    krsort($producers);
    foreach ($producers as $lid => $producer) {
        $cid = $producer['currency_id'];
        $production = get_building_production($resources, $producer);
        if ($producer['derivative']) $resources['producer_'.$cid.'_'.($lid-1)] = json_value($resources, 'producer_'.$cid.'_'.($lid-1)) + $production * json_value($resources, 'producer_'.$cid.'_'.$lid);
        else {
            $overall_production += $production * json_value($resources, 'producer_'.$cid.'_'.$lid);
        }
    }

    return $overall_production;
}

function get_vp_threshold($resources, $currency) {
    $cid = $currency['id'];
    return $currency['vp_start_cost'] * pow($currency['vp_cost_mult'], json_value($resources, 'vp_'.$cid));
}

// Actions

function calculate_changes(&$player_data, $producers, $currencies) {
    $pid = $player_data['internal_id'];
    $changes = get_changes_made($pid);

    foreach ($changes as $change) {
        $cid = $change['currency'];
        $lid = $change['level'];
        if (is_null($lid)) { // Sell all
            $player_data['resources']['money'] = json_value($player_data['resources'], 'money') + json_value($player_data['resources'], 'currency_'.$cid);
            $player_data['resources']['currency_'.$cid] = 0;
        }
        else { // Buy something
            if ($producers[$cid][$lid]['uses_zero_level']) $player_data['resources']['producer_'.$cid.'_0'] = json_value($player_data['resources'], 'producer_'.$cid.'_0') - 1;
            if (!is_null($producers[$cid][$lid]['prev_level_cost'])) $player_data['resources']['producer_'.$cid.'_'.($lid-1)] = json_value($player_data['resources'], 'producer_'.$cid.'_'.($lid-1)) - $producers[$cid][$lid]['prev_level_cost'];

            $cost = get_building_money_cost($player_data['resources'], $producers[$cid][$lid]);
            if ($cost > json_value($player_data['resources'], 'currency_'.$cid)) { // Cover rest with money
                $cost -= json_value($player_data['resources'], 'currency_'.$cid);
                $player_data['resources']['currency_'.$cid] = 0;
                $player_data['resources']['money'] = json_value($player_data['resources'], 'money') - $cost * $producers[$cid][$lid]['money_cost_mult'];
            }
            else $player_data['resources']['currency_'.$cid] = json_value($player_data['resources'], 'currency_'.$cid) - $cost;

            $player_data['resources']['producer_'.$cid.'_'.$lid] = json_value($player_data['resources'], 'producer_'.$cid.'_'.$lid) + 1;
            $player_data['resources']['bought_'.$cid.'_'.$lid] = json_value($player_data['resources'], 'bought_'.$cid.'_'.$lid) + 1;

            if ($currencies[$cid]['victory_condition'] == 0 && $lid == 4) {
                $player_data['vp'] += $currencies[$cid]['vp_per_level'];
                $player_data['resources']['vp_'.$cid] = json_value($player_data['resources'], 'vp_'.$cid) + 1;
                $player_data['resources']['rvp_'.$cid] = json_value($player_data['resources'], 'rvp_'.$cid) + $currencies[$cid]['vp_per_level'];
            }

            if ($currencies[$cid]['victory_condition'] == 1 && json_value($player_data['resources'], 'producer_'.$cid.'_'.$lid) >= get_vp_threshold($player_data['resources'], $currencies[$cid])) {
                $player_data['vp'] += $currencies[$cid]['vp_per_level'];
                $player_data['resources']['vp_'.$cid] = json_value($player_data['resources'], 'vp_'.$cid) + 1;
                $player_data['resources']['rvp_'.$cid] = json_value($player_data['resources'], 'rvp_'.$cid) + $currencies[$cid]['vp_per_level'];
            }
        }
    } 
}

function calculate_production(&$player_data, $producers, $currencies) {
    $pid = $player_data['internal_id'];

    foreach ($producers as $cid => $producer_row) {
        krsort($producer_row);
        foreach ($producer_row as $lid => $producer) {
            $production = get_building_production($player_data['resources'], $producer);
            if ($producer['derivative']) $player_data['resources']['producer_'.$cid.'_'.($lid-1)] = json_value($player_data['resources'], 'producer_'.$cid.'_'.($lid-1)) + $production * json_value($player_data['resources'], 'producer_'.$cid.'_'.$lid);
            else {
                $player_data['resources']['currency_'.$cid] = json_value($player_data['resources'], 'currency_'.$cid) + $production * json_value($player_data['resources'], 'producer_'.$cid.'_'.$lid);

                if ($currencies[$cid]['victory_condition'] == 1) {
                    while(json_value($player_data['resources'], 'producer_'.$cid.'_'.$lid) >= get_vp_threshold($player_data['resources'], $currencies[$cid])) {
                        $player_data['vp'] += $currencies[$cid]['vp_per_level'];
                        $player_data['resources']['vp_'.$cid] = json_value($player_data['resources'], 'vp_'.$cid) + 1;
                        $player_data['resources']['rvp_'.$cid] = json_value($player_data['resources'], 'rvp_'.$cid) + $currencies[$cid]['vp_per_level'];
                    }
                }

                if ($currencies[$cid]['victory_condition'] == 2) {
                    $player_data['resources']['vp_progress_'.$cid] = json_value($player_data['resources'], 'vp_progress_'.$cid) + $production * json_value($player_data['resources'], 'producer_'.$cid.'_'.$lid);
                    while (json_value($player_data['resources'], 'vp_progress_'.$cid) >= get_vp_threshold($player_data['resources'], $currencies[$cid])) {
                        $player_data['resources']['vp_progress_'.$cid] -= get_vp_threshold($player_data['resources'], $currencies[$cid]);
                        $player_data['vp'] += $currencies[$cid]['vp_per_level'];
                        $player_data['resources']['vp_'.$cid] = json_value($player_data['resources'], 'vp_'.$cid) + 1;
                        $player_data['resources']['rvp_'.$cid] = json_value($player_data['resources'], 'rvp_'.$cid) + $currencies[$cid]['vp_per_level'];
                    }
                }
            }
        }
    }
}

// Cards

function calculate_cards_effect(&$currencies, &$producers, $game_data) {
    $cards_log_db = perform_query('SELECT * FROM logs WHERE game_id = "'.$game_data['id'].'" AND type = "card_played" ORDER BY ts DESC');
    while ($card_log = $cards_log_db->fetch_assoc()) {
        $card_log['data'] = json_decode($card_log['data'], true);
        $card_data = get_card_info($card_log['data']['what']);

        if ($card_data['type'] == 6) $producers[$card_data['data']['industry']][0]['production_rate'] += 1; // 0th level building -- +1 income
        if ($card_data['type'] == 10) $currencies[$card_data['data']['industry']]['vp_per_level'] *= 2; // VP income is doubled
        if ($card_data['type'] == 11) break; // Permanent effects from prev round are disabled
        if ($card_data['type'] == 13 && $game_data['turn'] == $card_log['turn']) { // No production this round
            $cid = $card_data['data']['industry'];
            foreach ($producers[$cid] as &$producer) {
                $producer['production_rate'] = NULL;
            }
            unset($producer);
        }
    }
    $cards_log_db->free();
}

// END OF TURN. Major functions!

function implement_card_effect($game_id, $turn, $card, &$currencies, &$producers, &$player_data) {
    global $CONST;

    if ($card['type'] == 0) {
        // All players with the least ... lose random building

        $res_min = get_currency_production_proper($player_data[0]['resources'], $producers[$card['data']['industry']]);
        foreach ($player_data as $player) {
            $res_min = min($res_min, get_currency_production_proper($player['resources'], $producers[$card['data']['industry']]));
        }

        foreach ($player_data as &$player) {
            if (abs($res_min - get_currency_production_proper($player['resources'], $producers[$card['data']['industry']])) < $CONST['EPS']) {
                $building_count = 0;
                foreach ($currencies as $currency) {
                    foreach ($producers[$currency['id']] as $producer) {
                        $building_count += json_value($player['resources'], 'producer_'.$currency['id'].'_'.$producer['level']);
                    }
                }
                if ($building_count > 0) {
                    $building_count = rand(1, $building_count);
                    $destroyed = false;
                    foreach ($currencies as $currency) {
                        foreach ($producers[$currency['id']] as $producer) {
                            $building_count -= json_value($player['resources'], 'producer_'.$currency['id'].'_'.$producer['level']);
                            if (!$destroyed && $building_count <= 0) {
                                $player['resources']['producer_'.$currency['id'].'_'.$producer['level']] = json_value($player['resources'], 'producer_'.$currency['id'].'_'.$producer['level']) - 1;
                                // building_lost to log
                                perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'building_lost', '{\"who\": ".$player['internal_id'].", \"what\": ".$producer['id']."}')");
                                $destroyed = true;
                            }
                        }
                    }
                }
                else {
                    // building_none to log
                    perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'building_none', '{\"who\": ".$player['internal_id']."}')");
                }
            }
        }
        unset($player);
    }
    if ($card['type'] == 1) {
        // All players with the most ... get two 0th

        $res_max = get_currency_production_proper($player_data[0]['resources'], $producers[$card['data']['industry']]);
        foreach ($player_data as $player) {
            $res_max = max($res_max, get_currency_production_proper($player['resources'], $producers[$card['data']['industry']]));
        }

        foreach ($player_data as &$player) {
            if (abs(get_currency_production_proper($player['resources'], $producers[$card['data']['industry']]) - $res_max) < $CONST['EPS']) {
                $player['resources']['producer_'.$card['data']['industry'].'_0'] = json_value($player['resources'], 'producer_'.$card['data']['industry'].'_0') + 2;
                // building_gained x2 to log
                perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'building_gained', '{\"who\": ".$player['internal_id'].", \"what\": ".$producers[$card['data']['industry']][0]['id']."}')");
                perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'building_gained', '{\"who\": ".$player['internal_id'].", \"what\": ".$producers[$card['data']['industry']][0]['id']."}')");
            }
        }
        unset($player);
    }
    if ($card['type'] == 2) {
        // All players lose all banked ...

        foreach ($player_data as &$player) {
            $player['resources']['currency_'.$card['data']['industry']] = 0;
        }
        unset($player);
    }
    if ($card['type'] == 3) {
        // All players gain ... 3

        foreach ($player_data as &$player) {
            $player['resources']['currency_'.$card['data']['industry']] = json_value($player['resources'], 'currency_'.$card['data']['industry']) + 3;
        }
        unset($player);
    }
    if ($card['type'] == 4) {
        // All players' money are doubled

        foreach ($player_data as &$player) {
            $player['resources']['money'] = json_value($player['resources'], 'money') * 2;
        }
        unset($player);
    }
    if ($card['type'] == 5) {
        // All ... building costs are reset for all players

        foreach ($player_data as &$player) {
            foreach ($producers[$card['data']['industry']] as $producer) {
                $player['resources']['bought_'.$producer['currency_id'].'_'.$producer['level']] = 0;
            }
        }
        unset($player);
    }
    if ($card['type'] == 6) {
        // 0th produce ... 1 more for all players

        // No effect
    }

    if ($card['type'] == 7) {
        // All players with the most money gain 3 VP

        $res_max = json_value($player_data[0]['resources'], 'money');
        foreach ($player_data as $player) {
            $res_max = max($res_max, json_value($player['resources'], 'money'));
        }

        foreach ($player_data as &$player) {
            if (abs(json_value($player['resources'], 'money') - $res_max) < $CONST['EPS']) {
                $player['vp'] += 3;
                // vp_gained(3) to log
                perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'vp_gained', '{\"who\": ".$player['internal_id'].", \"amount\": 3}')");
            }
        }
        unset($player);
    }
    if ($card['type'] == 8) {
        // All players with the most ... gain 2 VP

        $res_max = get_currency_production_proper($player_data[0]['resources'], $producers[$card['data']['industry']]);
        foreach ($player_data as $player) {
            $res_max = max($res_max, get_currency_production_proper($player['resources'], $producers[$card['data']['industry']]));
        }

        foreach ($player_data as &$player) {
            if (abs(get_currency_production_proper($player['resources'], $producers[$card['data']['industry']]) - $res_max) < $CONST['EPS']) {
                $player['vp'] += 2;
                // vp_gained(2) to log
                perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'vp_gained', '{\"who\": ".$player['internal_id'].", \"amount\": 2}')");
            }
        }
        unset($player);
    }
    if ($card['type'] == 9) {
        // Everything related to ... is reset. Players retain VP earned through ... All players gain ... 3

        foreach ($player_data as &$player) {
            $player['resources']['currency_'.$card['data']['industry']] = 3;
            $player['resources']['vp_'.$card['data']['industry']] = 0;
            $player['resources']['rvp_'.$card['data']['industry']] = 0;
            foreach ($producers[$card['data']['industry']] as $producer) {
                $player['resources']['producer_'.$producer['currency_id'].'_'.$producer['level']] = 0;
                $player['resources']['bought_'.$producer['currency_id'].'_'.$producer['level']] = 0;
            }
        }
        unset($player);
    }
    if ($card['type'] == 10) {
        // All future VP income from ... is doubled for all players

        // No effect
    }
    if ($card['type'] == 11) {
        // All cards' permanent effects from previous rounds are disabled

        // No effect
    }
    if ($card['type'] == 12) {
        // All players with the most VP sell all their currencies for money

        $res_max = $player_data[0]['vp'];
        foreach ($player_data as $player) {
            $res_max = max($res_max, $player['vp']);
        }

        foreach ($player_data as &$player) {
            if (abs($player['vp'] - $res_max) < $CONST['EPS']) {
                $total_sold = 0;
                foreach ($currencies as $currency) {
                    $total_sold += json_value($player['resources'], 'currency_'.$currency['id']);
                    $player['resources']['currency_'.$currency['id']] = 0;
                }
                // currencies_sold to log
                perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$turn.", 'currencies_sold', '{\"who\": ".$player['internal_id'].", \"amount\": ".$total_sold."}')");
            }
        }
        unset($player);
    }
    if ($card['type'] == 13) {
        // ... buildings do not produce anything in the end of this round

        // No effect
    }   
}

function end_of_turn($game_id) {
    global $CONST;
    
    // First, get game data and check whether time is ripe
    $game_data = get_game_info($game_id);
    if (!is_null($game_data['future_cards'])) $game_data['future_cards'] = explode(',', $game_data['future_cards']);
    else $game_data['future_cards'] = array();

    $all_committed = TRUE;
    $player_data = get_game_players_info($game_id);
    foreach ($player_data as $player) {
        if (!$player['commit']) $all_committed = FALSE;
    }

    if (!$game_data['locked'] && (time() >= $game_data['last_update_ts'] + $CONST['TURN_LENGTH'] || $all_committed)) {
        // If so, lock other instances by updating game info and force-committing all players
        perform_query('UPDATE games SET turn = '.($game_data['turn'] + 1).", locked = 1 WHERE id = ".$game_data['id']);
        perform_query('UPDATE players SET commit = 1 WHERE game_id = '.$game_data['id']);

        // Now collect currencies and producers, calculate cards effect (we WILL recalculate this, just leave it like that for now)
        $currencies = get_currencies();
        $producers = get_producers();
        calculate_cards_effect($currencies, $producers, $game_data);

        // Collect player data
        $player_data = get_game_players_info($game_id);
        foreach ($player_data as &$other_player_data) {
            $other_player_data['resources'] = json_decode($other_player_data['resources'], true);
        }
        unset($other_player_data);

        // If it is card-playing round, fill any NULL's with random card
        // Also: collect and shuffle cards, and push them into future_cards
        if ($game_data['turn'] == 1 || $game_data['turn'] == 5) {
            $new_cards = array();
            foreach ($player_data as $player=>$data) {
                $data['cards'] = explode(',', $data['cards']);
                if (is_null($data['card_picked'])) $data['card_picked'] = $data['cards'][array_rand($data['cards'])];

                array_push($new_cards, $data['card_picked']);
                $data['cards'] = NULL;
                $data['card_picked'] = NULL;

                $player_data[$player] = $data;
            }

            shuffle($new_cards);
            foreach ($new_cards as $card) {
                array_push($game_data['future_cards'], $card);
            }
        }

        // If NEXT round is card-playing, prepare cards for play
        if ($game_data['turn'] == 4) {
            $cards_query = perform_query("SELECT id FROM cards WHERE onstart = 0");
            $cards = $cards_query->fetch_all(MYSQLI_ASSOC);
            $cards_query->free();
            shuffle($cards);

            foreach($player_data as $player=>$data) {
                $data['cards'] = array();
                for ($cnum = 0; $cnum < $CONST['CARD_CHOICES']; $cnum+=1) {
                    $curcard = array_pop($cards);
                    array_push($data['cards'], $curcard['id']);
                }
                $data['cards'] = implode(',', $data['cards']);
                $player_data[$player] = $data;
            }
        }

        // Count all changes made by players
        foreach ($player_data as $player=>$data) {
            calculate_changes($player_data[$player], $producers, $currencies);
        }

        // If there is a card and we are playing end-of-round card, play it
        if (count($game_data['future_cards']) > 0 && $game_data['turn'] <= 5) {
            $curcard = array_shift($game_data['future_cards']);
            $card_data = get_card_info($curcard);

            // Write to log
            perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$game_data['turn'].", 'card_played', '{\"what\": ".$curcard."}')");

            implement_card_effect($game_id, $game_data['turn'], $card_data, $currencies, $producers, $player_data);

            // Card probably did something funny, so we need to recalculate actions
            $currencies = get_currencies();
            $producers = get_producers();
            calculate_cards_effect($currencies, $producers, $game_data);
        }

        // Calculate production, now
        foreach ($player_data as $player=>$data) {
            calculate_production($player_data[$player], $producers, $currencies);
        }

        $game_data['turn'] += 1;

        // Start of round log
        perform_query("INSERT INTO logs (game_id, turn, type) VALUES (".$game_id.", ".$game_data['turn'].", 'turn_start')");

        // If there is a card and we are playing start-of-round card, play it
        if (count($game_data['future_cards']) > 0 && $game_data['turn'] > 5) {
            $curcard = array_shift($game_data['future_cards']);
            $card_data = get_card_info($curcard);

            // Write to log
            perform_query("INSERT INTO logs (game_id, turn, type, data) VALUES (".$game_id.", ".$game_data['turn'].", 'card_played', '{\"what\": ".$curcard."}')");

            implement_card_effect($game_id, $game_data['turn'], $card_data, $currencies, $producers, $player_data);
        }

        // Finally - update players
        foreach ($player_data as $data) {
            $data['resources'] = json_encode($data['resources']);
            if (!is_null($data['cards'])) $data['cards'] = "'".$data['cards']."'";
            else $data['cards'] = 'NULL';
            perform_query('UPDATE players SET vp = "'.$data['vp'].'", resources = \''.$data['resources'].'\', cards = '.$data['cards'].', card_picked = NULL, commit = 0 WHERE internal_id = '.$data['internal_id']);
            // clean actions
            perform_query("DELETE FROM actions WHERE player_id = ".$data['internal_id']);
        }

        // and update cards
        if (count($game_data['future_cards']) == 0) perform_query('UPDATE games SET future_cards = NULL, locked = 0 WHERE id = '.$game_data['id']);
        else {
            $game_data['future_cards'] = implode(',', $game_data['future_cards']);
            perform_query('UPDATE games SET future_cards = "'.$game_data['future_cards'].'", locked = 0 WHERE id = '.$game_data['id']);
        }
    }
}

?>
<?php

//
//php index.php mode=1 surname=ivanov name=ivan midname=ivanovich
//php index.php mode=2 id_patient=1
//php index.php mode=3 surname=ivanov name=ivan midname=ivanovich oms=3029539554903272

header('Content-Type: text/html; charset=CP1251');

parse_str(implode('&', array_slice($argv, 1)), $_GET);

print_r($_GET);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "root", "patient_db");
$mysqli->query("SET NAMES 'utf8';");

$mode = $_GET['mode'];
$surname = $_GET['surname'];
$name = $_GET['name'];
$midname = $_GET['midname'];
$oms = $_GET['oms'];
$id_patient = $_GET['id_patient'];

switch ($mode) {
    case 1:
        new_patient($surname, $name, $midname);
        break;
    case 2:
        add_test($id_patient);
        break;
    case 3:
        get_record($surname, $name, $midname, $oms);
        break;
}

function new_patient($surname, $name, $midname) {
    global $mysqli;
    $stmt = $mysqli->prepare("INSERT INTO patient(surname, name, midname, OMS) VALUES (?, ?, ?, ?)");

        generator($stmt, $surname, $name, $midname);
        generator($stmt, $surname, $midname, $name);
        generator($stmt, $name, $surname, $midname);
        generator($stmt, $name, $midname, $surname);
        generator($stmt, $midname, $name, $surname);
        generator($stmt, $midname, $surname, $name);

}

function generator($stmt, $surname, $name, $midname) {
    $oms = "";
    for ($i = 0; $i < 16; $i++) {
        $oms .= rand(0, 9);
    }
    $stmt->bind_param("ssss", $surname, $name, $midname, $oms);
    if ($stmt->execute() === true) {
        echo "Есть подключение";
    } else {
        echo "Error...";
    }
}

function add_test($id_patient) {
    global $mysqli;
    
    $persents_arr = array("93", "94", "96", "97");
    $persent = rand(0, 3);
    $mysqli->query("
        INSERT INTO test_type(name_test, accuracy_quality)
        VALUES('Lg(m)', '{$persents_arr[$persent]}')
    ");
    $_type = $mysqli->insert_id;
    
    $labs_arr = array("ANIVAc", "UniLab", "MMIc");
    $lab_index = rand(0, 2);
    $mysqli->query("
        INSERT INTO labs(name)
        VALUES('{$labs_arr[$lab_index]}')
    ");
    $id_labs = $mysqli->insert_id;
   
    $res_arr = array("plus", "minus");
    $res_index = rand(0, 1);
    $mysqli->query("
        INSERT INTO results(value)
        VALUES('{$res_arr[$res_index]}')
    ");
    $res_id = $mysqli->insert_id;
    
    $mysqli->query("
        INSERT INTO test(id_patient, date, id_test_type,, id_result)
        VALUES('{$id_patient}', NOW(), '{$_type}', '{}', '{$res_id}')
    ");
    $test_id = $mysqli->insert_id;
    return $test_id;
}

function get_record($surname, $name, $midname, $oms) {
    global $mysqli;
    $stmt = $mysqli->prepare("
        SELECT *
        FROM patient p
        LEFT JOIN test t ON p.id = t.id_patient
        LEFT JOIN test_type tt ON tt.id = t.id_test_type
        LEFT JOIN laboratory l ON l.id = t
        LEFT JOIN results r ON r.id = t.id_result
        WHERE p.surname = ? AND p.name = ? AND p.midname = ?
        AND p.OMS = ?
    ");
    $stmt->bind_param("ssss", $surname, $name, $midname, $oms);
    $red = array();
    if ($stmt->execute() === true) {
        $red = $stmt->get_result();
        $row = array();
        $res_array = array();
        $i = 0;
        while ($row = $red->fetch_array(MYSQLI_NUM)) {
            $res_array[$i] = $row;
            $i++;
        }
        print_r($res_array);
    } else {
        echo "Error...";
    }
    return $res_array;
}
<?php

class common_functions {

    function common_functions() {
    }

    function GetNoOfDays($DateF, $DateT) {
        return round(abs(strtotime($DateT) - strtotime($DateF)) / 86400, 0) + 1;
    }

    function sysconfig($config_name) {
        global $db, $db_system;
        $config = $db->prepare("SELECT * FROM $db_system.`_sysconfig` WHERE `config_name` LIKE :name");
        $config->execute(array(":name" => $config_name));
        if ($config->rowCount()) {
            $config_data = $config->fetch(PDO::FETCH_ASSOC);
            $value = $config_data["config_value"];
        } else {
            $value = "";
        }
        return $value;
    }

    function GetProgID($Prefix) {
        $Code = md5(uniqid($Prefix, true));
        return $Code;
    }

    function SetField($Data, $FieldLen = "8", $Adjust = "L") {
        $DataLen = strlen($Data);
        $ExtraSpace = $FieldLen - $DataLen;
        if ($ExtraSpace < 0)
            $ExtraSpace = 0;
        if ($Adjust == "C") {
            $Half = number_format($ExtraSpace / 2, 0, '', '') * 1;
            $OtherHalf = $ExtraSpace - $Half;
            $ExactField = str_repeat(" ", $Half) . $Data . str_repeat(" ", $OtherHalf);
        } elseif ($Adjust == "R")
            $ExactField = str_repeat(" ", $ExtraSpace) . $Data;
        else
            $ExactField = $Data;

        $ExactField = substr($ExactField . str_repeat(" ", $FieldLen), 0, $FieldLen);

        return $ExactField;
    }

    function GetHostName() {
        return gethostbyaddr($_SERVER['REMOTE_ADDR']);
    }

    function GetIPAdd() {
        return gethostbyname($_SERVER['REMOTE_ADDR']);
    }

    function datefromdb($date) {
        $new = explode("-", $date);
        $a = array($new[1], $new[2], $new[0]);
        return $n_date = implode("/", $a);
    }

    function datefromtable($date) {
        if (strpos($date, "/"))
            $new = explode("/", $date);
        elseif (strpos($date, "-"))
            $new = explode("-", $date);
        elseif (strpos($date, "."))
            $new = explode(".", $date);
        if ($new[2] < 100)
            if ($new[2] <= 50)
                $new[2] = $new[2] + 2000;
            else
                $new[2] = $new[2] + 1900;
        $new[0] = substr(number_format($new[0]) + 100, -2);
        $new[1] = substr(number_format($new[1]) + 100, -2);
        $a = array($new[2], $new[0], $new[1]);
        return $n_date = implode("-", $a);
    }
    

    function download_csv($columns, $records, $filename = "") {
        if ($filename === "") {
            $filename = uniqid("", true) . ".csv";
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        if (count($columns)) {
            $items = array();
            foreach ($columns as $column) {
                $items[] = $column["caption"];
            }
            fputcsv($output, $items);
        }
        foreach ($records as $record) {
            $items = array();
            foreach ($columns as $column) {
                $items[] = $record[$column["field"]];
            }
            fputcsv($output, $items);
        }
    }
    
    function print_register($options) {
        $columns = $options["columns"];
        $records = $options["records"];
        $title = $options["title"];
        $is_line_number = isset($options["is_line_number"]) ? $options["is_line_number"] : true;
        $is_flex = isset($options["is_flex"]) ? $options["is_flex"] : false;
        $is_draw_border = isset($options["is_draw_border"]) ? $options["is_draw_border"] : false;
        $force_render = isset($options["force_render"]) ? $options["force_render"] : false;
        $isCenter = isset($options["isCenter"]) ? $options["isCenter"] : false;
        $print = isset($options["print"]) ? $options["print"] : true;
        $company = strtoupper($this->sysconfig("company"));
        $address = strtoupper($this->sysconfig("branch"));
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Register</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" type="text/css" href="../css/w3-css.css">
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
            <style type="text/css" media="print">
            @media all{
                table thead {
                display: table-header-group;
                }
                table tbody {
                display: table-row-group;
                }
                thead, th {
                font-size: 90%;
                }
                tr.line td {
                border-bottom:1pt solid black;
                }
                p {
                font-size: 60%;
                margin: 0 0 0 0;
                padding: 0 0 0 0;
                }
            }
            </style>
        </head>
        <body>
            <div class="w3-row" style="font-size: 90%; width: 100%;">
            <table  style="border: 1px solid black;" <?php
            if ($is_draw_border) {
                echo "style=\"border-collapse: collapse;\"";
            }
            if ($isCenter) {
                echo " align=\"center\"";
            }
            ?>>
                <thead>
                <?php if (!isset($options["no-company"])) { ?>
                    <tr>
                    <th colspan="<?php echo count($columns["column"]); ?>" align="center" style="font-size: 110%;"><?php echo $company; ?></th>
                    </tr>
                    <tr>
                    <td colspan="<?php echo count($columns["column"]); ?>" align="center" style="font-size: 80%; margin: 0 0 0 0;"><?php echo $address; ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <th colspan="<?php echo count($columns["column"]); ?>" align="center" style="font-size: 110%;"><?php echo $title; ?></th>
                </tr>
                <tr>
                    <td colspan="<?php echo count($columns["column"]); ?>"  style="font-size: 40%;">&nbsp;</td>
                </tr>
                <?php if (isset($columns["group"])) { ?>
                    <tr style="font-size: 80%;">
                    <th></th>
                    <?php
                    foreach ($columns["group"] as $column) {
                        ?><th colspan="<?php echo $column["span"]; ?>"><?php echo $column["caption"]; ?></th>
                        <?php } ?>
                    </tr>
                <?php } ?>
                <tr style="font-size: 80%;">
                    <th></th>
                    <?php
                    foreach ($columns["column"] as $column) {
                    ?><th <?php
                        if (isset($column["sizeCalculated"])) {
                        echo " style=\"size: $column[sizeCalculated];\"";
                        } elseif (isset($column["size"]) AND!$is_flex) {
                        echo " style=\"size: $column[size];\"";
                        }
                        ?>><?php echo $column["caption"]; ?></th>
                        <?php
                    }
                    ?>
                </tr>
                </thead>
                <tbody style="font-size: 90%;"><?php
                $cnt = 0;
                foreach ($records as $record) {
                    ?>
                    <tr <?php
                    if ($record["summary"]) {
                    echo "style=\"font-weight: bolder;\"";
                    } else {
                    if ($is_draw_border) {
                        echo "style=\"border: 1px solid black;\"";
                    } elseif (isset($record["style"])) {
                        echo "style=\"$record[style]\"";
                    }
                    }
                    ?>>
                    <td align="right"><?php
                        if ($is_line_number) {
                            if (!$record["summary"]) {
                                echo number_format(++$cnt) . ".";
                            }
                        }
                    ?></td>
                    <?php foreach ($columns["column"] AS $column) {
                        ?>
                        <td <?php
                        if (isset($column["attr"])) {
                        echo $column["attr"];
                        } elseif (isset($column["render"])) {
                        if ($column["render"] !== "date") {
                            echo "align=right";
                        }
                        }
                        ?>><?php
                            if (strpos($column["render"], ":")) {
                                $pos = substr($column["render"], strpos($column["render"], ":") + 1);
                                if ($force_render AND !is_null($record[$column["field"]])) {
                                    echo number_format($record[$column["field"]], $pos);
                                } elseif (number_format($record[$column["field"]], $pos, '.', '') !== number_format(0, $pos, '.', '')) {
                                    echo number_format($record[$column["field"]], $pos);
                                }
                            } else {
                                if ($column["render"] === "date") {
                                    $date = new DateTime($record[$column["field"]]);
                                    if (strlen($record[$column["field"]]) <= 10) {
                                        echo $date->format("m/d/Y");
                                    } else {
                                        echo $date->format("m/d/Y H:i:s");
                                    }
                                } else {
                                    echo $record[$column["field"]];
                                }
                            }
                            ?></td>
                    <?php } ?>
                </tr>
                    <?php }
                    ?></tbody>
            </table>
            <?php
            if (isset($options["footnote"])) {
                echo "<span style=\"font-size: 100%; float: right;\">$options[footnote]</span><br>";
            }
            if (isset($options["footnote-date"])) {
                echo "<span style=\"font-size: 50%; float: right;   \">/date printed: " . date("m/d/Y H:i:s") . "</span><br>";
            }
            ?>
            </div>
        </body>
        </html>
        <?php if ($print) { ?>
        <script>
            window.onload = function () {
            window.print();
            };
        </script>
        <?php } ?>
        <?php
    }

}

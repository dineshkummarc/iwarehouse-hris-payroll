<?php

function report_file($column, $records, $title, $subtitle, $subtitle2 = "") {
    $ccount = count($column) + 1;
    $image = "<!DOCTYPE html><html><HEAD><TITLE>$title</TITLE><style>@media print{thead{display: table-header-group;}}</style></HEAD><body>";
    $image.="<table align=center>";
    $image.="<tr align='center'><th colspan='$ccount'>$title</th></tr>";
    $image.="<tr align='center'><th colspan='$ccount'>$subtitle</th></tr>";
    if ($subtitle2 !== "") {
        $image.="<tr align='center'><th colspan='$ccount'>$subtitle2</th></tr>";
    }
    $image.="<tr align='center'><th colspan='$ccount'>&nbsp;</th></tr>";
    $image.="<tr align='center'><th></th>";
    for ($index = 0; $index <= count($column); $index++) {
        $col_name = $column[$index]["caption"];
        $image.="<th>$col_name</th>";
    }
    $image.="</tr><tr><th colspan='$ccount'><hr></th></tr></thead><tbody>";
    for ($index = 0; $index <= count($records); $index++) {
        $image.="<tr><td></td>";
        for ($ndx = 0; $ndx <= count($column); $ndx++) {
            $attr = $column[$ndx]["attr"];
            $image.="<td $attr>";
            if ($column[$ndx]["render"] === "date") {
                $date = new DateTime($records[$index][$column[$ndx]["field"]]);
                if ($date->format("m/d/Y") !== "01/01/1970") {
                    $image.=$date->format("m/d/Y");
                }
            } elseif (substr($column[$ndx]["render"], 0, 5) === "float" OR substr($column[$ndx]["render"], 0, 3) === "int") {
                if (substr($column[$ndx]["render"], 0, 5) === "float") {
                    if (strpos($column[$ndx]["render"], ":")) {
                        $dec = substr($column[$ndx]["render"], strpos($column[$ndx]["render"], ":"));
                    } else {
                        $dec = 0;
                    }
                    $image.=number_format($records[$index][$column[$ndx]["field"]], $dec);
                } else {
                    $image.=number_format($records[$index][$column[$ndx]["field"]], 0);
                }
                $image.=$records[$index][$column[$ndx]["field"]];
            } else {
                $image.=$records[$index][$column[$ndx]["field"]];
            }
            $image.="</td>";
        }
        $image.="</tr>";
    }
    $image.="</tbody></table>";
    $image.="</body></html>";
    return $image;
}

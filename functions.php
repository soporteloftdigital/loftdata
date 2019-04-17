<?php
global $wpdb;
$data_in = $_POST['form_collection'];
$data = str_replace('\\', '', $data_in);
$form_collection = json_decode($data);
$field_status; $ld_colsnames; $log; $num;
$insert = false;
//GetExists
function &getExistencia($client, $project, $form)
{
    global $wpdb;
    //Client verify
    $wpdb->get_results($client);
    $num_c = $wpdb->num_rows;
    if($num_c==0):
        $log ="No existe cliente. ";
    else:
        $log = "";
    endif;
    //Project verify
    $wpdb->get_results($project);
    $num_p = $wpdb->num_rows;
    if($num_p==0):
        $log = $log."No existe proyecto. ";
    else:
        $log = "";
    endif;
    //Form verify
    $wpdb->get_results($form);
    $num_f = $wpdb->num_rows;
    if($num_f==0):
        $log = $log."No existe formulario";
    else:
        $log = "";
    endif;
    return $log;
}
/*Get client ip*/
function &getRealIpAddr()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP']))
  {
    $ip=$_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  //to check ip is pass from proxy
  {
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  else
  {
    $ip=$_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}
//Verify fields on db
foreach($form_collection as $item) :
    foreach($item as $nombre_campo => $valor_campo) :
        //Verify API
        if($nombre_campo == "API_KEY"):
            $rest_api = explode("|", $valor_campo);
            if($insert==false): 
                $q_client = "SELECT client_id FROM `ld_lfdata_forms` where client_id = '".$rest_api[0]."' ";
                $q_project = "SELECT project_id FROM `ld_lfdata_forms` where project_id = '".$rest_api[1]."' ";
                $q_form = "SELECT form_id FROM `ld_lfdata_forms` where form_id = '".$rest_api[2]."' ";
                $general_log = &getExistencia($q_client, $q_project, $q_form);
                //If no exists client, project or form id
                if($general_log <> "" ):
                    $insert = false;
                    $field_status = true;
                endif;
                if($general_log == "" ):
                    $insert = true;
                endif;
            endif;
        endif;
        //Verify if field exist on db
        if($nombre_campo == "nombre_campo" && $insert == true):
            $wpdb->get_results("SHOW COLUMNS FROM `ld_lfdata_main` LIKE '".$valor_campo."'");
            $num = $wpdb->num_rows;
            if($num==0):
                $wpdb->query("ALTER TABLE `ld_lfdata_main` ADD ".$valor_campo." varchar(100) NOT NULL");
            endif;
            $field_status = true;
            $ld_colsnames = $ld_colsnames. "`".$valor_campo."` ,";
        elseif($nombre_campo == "valor"):
            $ld_values = $ld_values."'".$valor_campo."' ,";
        endif;
    endforeach;
endforeach;
//Insert data
if($field_status==true):
    $ld_colsnames = rtrim($ld_colsnames, ',');
    $ld_values = rtrim($ld_values, ',');
    $api_key = $rest_api[0].'|'.$rest_api[1].'|'.$rest_api[2];
    $client_ip = &getRealIpAddr();
    //If no exists client, project or form id
    if($general_log <> "" ):
        $general_log = "Se encontraron los siguientes errores: ".$general_log;
        $query_log = "INSERT INTO `ld_lfdata_fail` (`id`, `ip`, `api_key`, `form_id`, `log`) VALUES (NULL, '".$client_ip."', '".$api_key."', '".$rest_api[2]."', '".$general_log."');";
        $wpdb->query($query_log);
    //If exist inser on data base
    elseif($general_log == "" ):
        $query = "INSERT INTO `ld_lfdata_main`(  `api_key`, ".$ld_colsnames.") VALUES (' ".$api_key."' ,".$ld_values.");";
        $wpdb->query($query);
    endif;
endif;
?>
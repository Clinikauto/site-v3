<?php
ini_set("display_errors",1); error_reporting(E_ALL);
require_once __DIR__."/config.php";
require_once __DIR__."/includes/catalog_store.php";

echo "=== 1. Connexion DB ===\n";
$conn = catalog_db_connection();
if ($conn instanceof mysqli) { echo "Connexion DB : OK server=".$conn->server_info."\n"; }
else { echo "Connexion DB : ECHEC — mode JSON\n"; exit(1); }
echo "catalog_using_database() : ".(catalog_using_database()?"DB":"JSON")."\n";

echo "\n=== 2. Creation annonce vehicule ===\n";
$payload=["id"=>"0","type"=>"vehicle","title"=>"Test Diag ".date("H:i:s"),
  "subtitle"=>"ST","price"=>"4999","short_description"=>"Courte desc",
  "description"=>"Description longue","specs"=>"Marque : Peugeot\nModele : 308",
  "status"=>"available","payment_confirmed"=>"0"];

list($ok,$errs,$item) = catalog_upsert_item($payload,[],[]);
echo "success : ".($ok?"TRUE":"FALSE")."\n";
if(!empty($errs)) echo "Erreurs : ".implode("|",$errs)."\n";
if($ok){
  $id=(int)($item["id"]??0);
  echo "ID sauve : $id\n";
  $v=catalog_find_item($id);
  echo "Verify DB : ".($v?"PRESENTE id=".$v["id"]:"ABSENTE !")."\n";
  echo "\n=== 3. Suppression (nettoyage) ===\n";
  $del=catalog_delete_item($id);
  echo "Delete : ".($del?"OK":"ECHEC ".catalog_get_runtime_error())."\n";
  echo "Post-delete : ".(catalog_find_item($id)===null?"ABSENTE OK":"ENCORE LA !")."\n";
} else {
  echo "Runtime error : ".catalog_get_runtime_error()."\n";
}
echo "\n=== Fin ===\n";


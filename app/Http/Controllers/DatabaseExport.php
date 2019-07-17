<?php

namespace App\Http\Controllers;
use Excel;
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use DB;
use Alert;
//use App\Http\Requests;
use Artisan;
use Log;
use Storage;
use Illuminate\Support\Facades\Schema;

class DatabaseExport extends Controller
{
    public function export(){

        $name =  env("DB_DATABASE");
        DB::select("SET NAMES 'utf8'");
        $queryTables = DB::select('SHOW TABLES');

   
        foreach ($queryTables as  $table) {
                $table_string = 'Tables_in_'.$name;
                $target_tables[] = $table->$table_string;
        } 
        
        $content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET AUTOCOMMIT = 0;\r\nSTART TRANSACTION;\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;";

        $content .="\r\n\r\n--\r\n-- Database: `".$name."`\r\n--\r\n";

       foreach($target_tables as $table){

           
           //$content.= '\r\n\r\n--\r\n-- Table structure for table `'.$table.'`';
           $content.= "\r\n-- --------------------------------------------------------\r\n";

           $content.= "\r\n--\r\n-- Table structure for table `".$table."`"."\r\n--\r\n";

           $result = DB::table($table)->get(); 
           
           $row = count($result);

           $all_fields = count(Schema::getColumnListing($table));

           $field_names = Schema::getColumnListing($table);

           $res = DB::select('SHOW CREATE TABLE `'.$table."`");

           $drop = "DROP TABLE IF EXISTS `".$table."`;\r\n";


           $content.= "\r\n".$drop ;

           foreach ($res as $key => $value) {

               $createTable[$key] = str_ireplace("CREATE TABLE `", "CREATE TABLE IF NOT EXISTS `",$value->{'Create Table'});
               
               $content.= $createTable[$key].";";

           }


           if($row){

               $content.= "\r\n\r\n--\r\n-- Dumping data for table `".$table."`"."\r\n--\r\n\r\n";
               
               $content.= "INSERT INTO `".$table."` (" ;

               foreach ($field_names as $field_num => $f_name) {
                   
                   $content.="`".$f_name."`";

                   if($field_num < ($all_fields-1)){

                       $content.=", " ;
                   }

               }

               $content.= ") VALUES\r\n";

               $row_count = 0;
             
               foreach ($result as $keys => $rowData) {

                   $content.="(";

                   $field_count = 0;

                   foreach ($rowData as $keyss => $val) {
                    
                       if(gettype ( $val ) == 'integer'){
                           $content.= $val ;
                       }else{
                           $content.="'".$val."'" ;                                
                       }

                       if($field_count < ($all_fields-1)){

                           $content.=", ";

                       }

                       $field_count=$field_count+1;    
                   }

                   $content.=")";

                   if($row_count < ($row-1)){

                       $content.=",\r\n";
                   }

                   $row_count=$row_count+1;

               }

               $content.=";";

           }
   
           
       $content .="\n";

       }
      
       $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";

       //$backup_name = 'backup';
       $backup_name = '';
       $backup_name = $backup_name ? $backup_name : $name.'_'.date('Ymdhis').'.sql';

       ob_get_clean(); header('Content-Type: application/octet-stream');
       header("Content-Transfer-Encoding: Binary");
       header('Content-Length: '. (function_exists('mb_strlen') ? mb_strlen($content, '8bit'): strlen($content)) ); 
       header("Content-disposition: attachment; filename=\"".$backup_name."\""); 
       echo $content; exit;

    }
}

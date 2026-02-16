<?php 

$globalVar = 'La función se ha ejecutado correctamentes';
function myfuntion(){
    global $globalVar;
    echo $globalVar;
}

?>
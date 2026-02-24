<?php 

$globalVar = 'La función se ha ejecutado correctamente';
function myfuntion(){
    global $globalVar;
    echo $globalVar;
}

?>
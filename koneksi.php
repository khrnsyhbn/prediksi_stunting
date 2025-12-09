<?php 
$host="localhost";
$user="root";
$password="";
$database="fuzzy_mamdani";

$koneksi = mysqli_connect($host, $user, $password, $database);

//memeriksa koneksi
if(!$koneksi)
	die("Koneksi Gagal");

//echo "koneksi berhasil ke databse" ." ".$database;
?>
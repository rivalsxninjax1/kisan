<?php
 	session_start();
	if(!isset($_SESSION['logged_in']) OR $_SESSION['logged_in'] == 0)
	{
		$_SESSION['message'] = "You need to first login to access this page !!!";
		header("Location: Login/error.php");
	}

 ?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>KISAN</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="description" content="" />
		<meta name="keywords" content="" />
		<link href="bootstrap\css\bootstrap.min.css" rel="stylesheet">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="bootstrap\js\bootstrap.min.js"></script>
		<!--[if lte IE 8]><script src="css/ie/html5shiv.js"></script><![endif]-->
		<link rel="stylesheet" href="login.css"/>
		<link rel="stylesheet" type="text/css" href="indexFooter.css">
		<script src="js/jquery.min.js"></script>
		<script src="js/skel.min.js"></script>
		<script src="js/skel-layers.min.js"></script>
		<script src="js/init.js"></script>
		<noscript>
			<link rel="stylesheet" href="css/skel.css" />
			<link rel="stylesheet" href="css/style.css" />
			<link rel="stylesheet" href="css/style-xlarge.css" />
		</noscript>
		<!--[if lte IE 8]><link rel="stylesheet" href="css/ie/v8.css" /><![endif]-->
	</head>
	<?php require 'menu.php'; ?>
	<body>



		<!-- One -->
			<section id="one" class="wrapper style1 align-center" style="height: 600px">
				<div class="container">
					<h2>Welcome to Digital Bazar</h2>
					<br /><br />
					<div class="row 200%">
						<section class="4u 12u$(small)">
							<a href="profileView.php"><img src="profileDefault.png"></a>
							<p>Your Profile</p>
						</section>
						<section class="4u 12u$(small)">
							<a href="productMenu.php?n=1" name="catSearch"><img src="search.png"></a>
							<p>Search according to your needs</p>
						</section>
						<section class="4u$ 12u$(small)">
							<a href="productMenu.php?n=0"><img src="product.png"></a>
							<p>Our products</p>
						</section>
					</div>
				</div>
			</section>



		 <!-- Footer -->
		 <footer class="footer-distributed" style="background-color:black" id="aboutUs">
        <center>
            <h1 style="font: 35px calibri;">About Us</h1>
        </center>
        <div class="footer-left">
            <h3 style="font-family: 'Times New Roman', cursive;">KISAN &copy; </h3>
            <div class="logo">
                <a href="index.php"><img src="images/logo.png" width="200px"></a>
            </div>
            <br />
            <p style="font-size:20px;color:white">Your product Our market !!!</p>
            <br />
        </div>

        <div class="footer-center">
            <div>
                <i class="fa fa-map-marker"></i>
                <p style="font-size:20px">KISAN Family<span>biratnagar</span></p>
            </div>
            <div>
                <i class="fa fa-phone"></i>
                <p style="font-size:20px">+977 9742270999</p>
            </div>
            <div>
                <i class="fa fa-envelope"></i>
                <p style="font-size:20px"><a href="sabinynwa@gmail.com" style="color:white">sabin@gmail.com</a></p>
            </div>
        </div>

        <div class="footer-right">
            <p class="footer-company-about" style="color:white">
                <span style="font-size:20px"><b>About KISAN</b></span>
                KISAN  is e-commerce trading platform for farmers...
            </p>
            <div class="footer-icons">
                <a  href="#"><i style="margin-left: 0;margin-top:5px;"class="fa fa-facebook"></i></a>
                <a href="#"><i style="margin-left: 0;margin-top:5px" class="fa fa-instagram"></i></a>
                <a href="#"><i style="margin-left: 0;margin-top:5px" class="fa fa-youtube"></i></a>
            </div>
        </div>

    </footer>




	</body>
</html>

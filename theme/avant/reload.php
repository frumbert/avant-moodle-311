<!DOCTYPE html PUBLIC"-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Iframe Refresh</title>
<script src="//code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="//code.jquery.com/jquery-migrate-1.1.1.min.js"></script>
<style>
body{font-family:sans-serif;font-size:16px;background-color:#fff;}
div{padding: 50px;text-align:center;}
a {background-color: #0056a4; border-radius: 4px; box-shadow:0 0 3px rgba(0,0,0,.1); color: #fff; padding: 5px 10px; display: inline-block; text-decoration: none; transition: background-color .2s ease; }
a:hover {background-color: #2683db; }
</style>
</head>

<body>

<div>
<h1>Thank you.</h1>
<a href="#" id="link" target="_parent">Save &amp; Close Survey</a>
</div>
<script type="text/javascript">
$(document).ready(function () {
	var a = $("a[href*='/course/view.php?id=']", parent.document);
	a.each(function (index, el) {
		console.log(index, el);
		var instance = $(this);
		if (instance.closest("nav").length) {
			var href = instance.attr("href");
			$("#link").attr("href", href);
			parent.location.href = href;
		};
	});
});
</script>
</body>
</html>
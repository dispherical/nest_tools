<?php 
    $output = shell_exec('cd /home/david && PATH=/home/david/.nix-profile/bin:/nix/var/nix/profiles/default/bin:/usr/local/bin:/usr/bin:/bin:/usr/local/games:/usr/games neofetch');
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <script src="https://rawcdn.githack.com/drudru/ansi_up/v5.2.1/ansi_up.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>/neofetch!</title>
</head>
<body>

    <h2>neofetch!</h2>
    <pre><code id="neofetch"></code></pre>
</body>
<script>
var ansi_up = new AnsiUp();
document.querySelector("#neofetch").innerHTML = ansi_up.ansi_to_html(<?php echo json_encode($output)?>).trim()
</script>
</html>

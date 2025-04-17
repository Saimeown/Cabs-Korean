<?php
function redirectToLoginIfNeeded($isLoggedIn)
{
    if (!$isLoggedIn) {
        echo "<script>
            alert('Please log in to access this feature.');
            window.location.href = 'login.php';
        </script>";
        exit();
    }
}

function disableIfNotLoggedIn($isLoggedIn)
{
    if (!$isLoggedIn) {
        echo 'disabled onclick="alert(\'Please log in to use this feature.\');"';
    }
}
?>

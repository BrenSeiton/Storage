<!DOCTYPE html>
<html>
    <head>
        <title>Admin Login</title>
        <link rel="stylesheet" href="style.css">
        <script src="script.js" defer></script>
    </head>
    <body>
        <h1>Inventory Management Dashboard </h1>
        <div id="form"class="container">

        </div>
        <div class="container">

            <button onclick="loadloginform()">Go to Login</button>
            <button onclick="loadregisterform()">Go to register a member</button>

        </div>
    </body> 

    <script>
        function loadloginform(){

            //newbie code ko ja idk i like this whahhaha pero iiba to after redesign
            // i literally placed the whole form ridya whahaha

            //basically the div nam may id form will be replaced by this whole html snippet
            //ja here is the form sa login just take note kng name='' kay amu ra ang i transfer sa query karun basically 'username' kun ano input na, pwede ta itransfer sa php variable $username then butang sa query. daw si name= ang makaput kng gin input mo
            document.getElementById("form").innerHTML = '<h1>Log In</h1><br/><form action="login.php" method="post"><input type="text" name="username" placeholder="Username" required><br/><input type="password" name="password" placeholder="Password" required><br><button type="submit">Login</button></form><br><br>';

        }
        function loadregisterform(){
            //ja add member yknow
            document.getElementById("form").innerHTML = '<h1>Register</h1><br/><form action="register.php" method="post"><input type="text" name="fullname" placeholder="FullName" required><br/><input type="text" name="username" placeholder="Username" required><br/><input type="password" name="password" placeholder="Password" required><br>  <p> select  role:</p> <input type="radio" id="staff" name="role" value="Staff" required> <label for="staff">Staff</label><br> <input type="radio" id="admin" name="role" value="Admin" required> <label for="admin">Admin</label><br> <input type="radio" id="cashier" name="role" value="Cashier" required> <label for="cashier">Cashier</label><br><button type="submit">Register</button></form><br><br>';

        }



    </script>

</html>

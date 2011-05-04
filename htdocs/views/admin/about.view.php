<?php

/*
 * ./htdocs/views/admin/about.view.php
 *
 * Copyright (C) 2011  Chris Howie <me@chrishowie.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__) . '/../master.view.php');

class AdminAboutView
    extends MasterView
{
    protected function getTitle()
    {
        return "About";
    }

    protected function getMenuId()
    {
        return "about";
    }

    protected function renderBody()
    {
?>

<div id="about">

<p>bitcoin-mining-proxy &copy; 2011 <a href="http://www.chrishowie.com/">Chris Howie</a>.</p>

<p>This software is free and open!  Please see the <a href="https://github.com/cdhowie/Bitcoin-mining-proxy">project page on GitHub</a> to obtain a copy of the sources.</p>

<p>This software may be distributed or hosted under the terms of the <a href="http://www.gnu.org/licenses/agpl.html">GNU Affero General Public License</a>, version 3 or later.  In section 13, the verbiage "users interacting with [this software] remotely through a computer network" shall apply only to those individuals having the proper credentials to authenticate with either the admin interface or the getwork proxy.  However, the software will, by default, comply with this requirement for all remote users.</p>

<p>Writing this proxy script took time; please consider donating some bitcoins if you found it useful, after verifying that the address below is GPG-signed by my key (2B7AB2808B1221CC260ADF656FCE505ACF8338F5).</p>

<pre>-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA256

Donations for bitcoin-mining-proxy may be sent to 14QiU9SXA4bxUzWPrxLxcC5p5NsnhnuR6p
-----BEGIN PGP SIGNATURE-----
Version: GnuPG v1.4.10 (GNU/Linux)

iQEcBAEBCAAGBQJNwcp8AAoJEG/OUFrPgzj1vyYH/iFBLnOyO9VLuw4vhK57jYTj
H/7fz4p59uIsBZUsdzUMVkXKoBf50hDSM1t0la/kUF5VyrzuGh/n6WlA/ZxFqa+r
F3IbPFGSYD8/abSjeGR6g4L4glJgZmZ4otqRW3llGIRi3RSnhtvHBM66qeHZw/0A
X9IjBm3oPhBNFoJFvv20fIkhjOiWL7cCeH5/Ik+zgbc6Ag/JpD9bZNi1MxVRX8+u
EPZZ1zsnjSf3W3K8NNwz8gOgEEMOpeRpNnXP4b7rDBDygRwYDmQfPmepUwRX3euU
vuITqTYcoWybeE4YCdJS40UJTrrExR/S6yJdy2Osw1ZaKnvuOPcCMef8XI2vYD4=
=hKuW
-----END PGP SIGNATURE-----</pre>

<p>Icons are from the excellent <a href="http://www.famfamfam.com/lab/icons/silk/">silk icon set by famfamfam</a>.  They are dual-licensed under <a href="http://creativecommons.org/licenses/by/2.5/">CC-BY-2.5</a> and <a href="http://creativecommons.org/licenses/by/3.0/">CC-BY-3.0</a>.</p>

</div>

<?php
    }
}

?>

#!/usr/bin/perl
#!/usr/bin/perl -w 

#
#  -------------------------------------------------------------------------
#  LICENSE
#
#  This file is part of PDF plugin for GLPI.
#
#  PDF is free software: you can redistribute it and/or modify
#  it under the terms of the GNU Affero General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  PDF is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#  GNU Affero General Public License for more details.
#
#  You should have received a copy of the GNU Affero General Public License
#  along with Reports. If not, see <http://www.gnu.org/licenses/>.
#
# @author    Nelly Mahu-Lasson, Remi Collet, Teclib
# @copyright Copyright (c) 2009-2022 PDF plugin team
# @license   AGPL License 3.0 or (at your option) any later version
# @link      https://github.com/pluginsGLPI/pdf/
# @link      http://www.glpi-project.org/
# @package   pdf
# @since     2009
#             http://www.gnu.org/licenses/agpl-3.0-standalone.html
#  --------------------------------------------------------------------------
#

if (@ARGV!=2){
print "USAGE update_po.pl transifex_login transifex_password\n\n";

exit();
}
$user = $ARGV[0];
$password = $ARGV[1];

opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `wget --user=$user --password=$password --output-document=locales/$_ http://www.transifex.net/api/2/project/GLPI_behaviors/resource/glpipot/translation/$lang/?file=$_`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  


<script>
//JG - włączenie śledzenia na potrzeby oszacowania użycia zasobów serwera




</script>


<table class="content">
    <tr><td class="content2-pagetitle"><img src="tpl/stdstyle/images/blue/stat1.png" class="icon32" alt="Statystyki" title="Statystyki" align="middle" /><font size="4">  <b>{{statistics}}</b></font></td></tr>
    <tr><td class="spacer"></td></tr>
</table>



<script type="text/javascript">
    TimeTrack("START");
</script>

<?php
global $debug_page;
if ($debug_page)
    echo "<script type='text/javascript'>TimeTrack( 'DEBUG' );</script>";
?>

<table width="760" class="table" style="line-height: 1.6em; font-size: 10px;">
    <tr>
        <td><?php include ("t2.php"); ?>
        </td></tr>
</table>

<script type="text/javascript">
    TimeTrack("END", "S2");
</script>

<?php
	/**
	 *
	 *	Информация по коммитам
	 *
	 */
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>commits</title>
		<?php echoStyle()?>
	</head>

<body>

<?php

	//таблица перекодировки имен
	$users['teodorus']='Fedor Seleckiy (teodorus)';
	$users['phobos-git']='Roman Khitriy (phobos-git)';
	$users['valtasar']='Monk (valtasar)';
	$users['Yourentus']='Yuri Simonenko (Yourentus)';
	$users['Alexandr']=' Alexander Sergeev (Alexandr)';

	//смещение времени в часах
	$defaultTime=-4; 
	
	//connect db
	$dbHost="localhost";
	$dbLogin="ghmon";
	$dbPassword="SdyDpPH5WWuNUAnt";
	$dbName="gh_mon";
	//$dbLogin="root";
	//$dbPassword="";
	//$dbName="test";
	$countRows=100;
	$table='commits';
	$dbL = @mysql_connect($dbHost,$dbLogin,$dbPassword);
	if (!@mysql_select_db($dbName,$dbL)) { }
	
	//разбор $_POST
	$where=getWhere($defaultTime);
	
	//numRows
	$sqlStr='SELECT * FROM '.$table.' '.$where.' ORDER BY `time` DESC';
	$sql=mysql_query($sqlStr);
	$numRows = mysql_num_rows($sql);

	//number page
	$page = isset($_GET['page']) ? $_GET['page'] : 0;

	echo'<form action="ad.php" name="filter" method="post">';// onchange="document.filter.submit()">';
	
	//фильтры
	echo'<table id="customers">';
	createFilter($table);
	echo'</table>';
	
	//сводная таблицы
	showSummaryData($table, $where, $users);
	
	
	echo'<table id="customers" width=100%>';
	echo '<tr><th>time</th><th>name</th><th>repo</th><th>mail</th><th>hash</th><th>status</th><th>ref</th><th>message</th><th>Строк добавлено</th><th>Строк удалено</th><th>Файлов изменено</th><th>Файлов добавлено</th><th>Файлов удалено</th></tr>';
	
	//данные таблицы
	showData($table, $where, $countRows, $page, $users);
	
	echo '</table>';
	echo '</form>';
	
	//prev - next
	createPrevNext($numRows, $countRows, $page);
	
?>


</body>
</html>

<?php 	
	/**
	 *	Стиль таблицы
	*/
	function echoStyle()
	{
		?>
			<style type='text/css'>
				#customers
				{
					border-collapse:collapse;
				}
				#customers td, #customers th 
				{
					font-size:1.0em;
					border:1px solid #777777;
					padding:3px 7px 2px 7px;
				}
				#customers th 
				{
					font-size:1.2em;
					text-align:left;
					padding-top:5px;
					padding-bottom:4px;
					background-color:#555555;
					color:#fff;
				}
			</style>
		<?php
	}
	
	/**
	 *	функция парсит $_POST и возвращает строку условий $where
	 *
	 *	@param		int		$defaultTime	смещение времени в часах
	 *	@return		string 					строка условий $where
	*/
	function getWhere($defaultTime)
	{
		$where='WHERE 1';
		
		if (isset($_POST['name']))
		{
			$s=' AND (';
			for ($i=0; $i<count($_POST['name']); $i++)
			{
				$s=
				$s.=$i==0 ? ' `a_name`="'.$_POST['name'][$i].'"' : ' OR `a_name`="'.$_POST['name'][$i].'"';
			}
			$s.=' )';
			if ($_POST['name'][0]=='all_name') $s='';
			$where.=$s;
		}

		if (isset($_POST['repo']))
		{
			$s=' AND (';
			for ($i=0; $i<count($_POST['repo']); $i++)
			{
				$s=
				$s.=$i==0 ? ' `repo`="'.$_POST['repo'][$i].'"' : ' OR `repo`="'.$_POST['repo'][$i].'"';
			}
			$s.=' )';
			if ($_POST['repo'][0]=='all_repo') $s='';
			$where.=$s;
		}
		
		if (isset($_POST['time']))
		{
			$m=date('n');
			$y=date('Y');
			$w=date('W');
			if ($_POST['time']=='today') $where.=' AND TO_DAYS(NOW()+ INTERVAL '.$defaultTime.' HOUR)= TO_DAYS(`time`+ INTERVAL '.$defaultTime.' HOUR)';
			if ($_POST['time']=='yesterday') $where.=' AND TO_DAYS(NOW()+ INTERVAL '.$defaultTime.' HOUR) - TO_DAYS(`time`+ INTERVAL '.$defaultTime.' HOUR)=1';
			if ($_POST['time']=='month') $where.=' AND MONTH(`time`+ INTERVAL '.$defaultTime.' HOUR)='.$m.' AND YEAR(`time`+ INTERVAL '.$defaultTime.' HOUR)='.$y;
			if ($_POST['time']=='week') $where.=' AND WEEK(`time`+ INTERVAL '.$defaultTime.' HOUR,1)='.$w.' AND YEAR(`time`+ INTERVAL '.$defaultTime.' HOUR)='.$y;
		} else {
			 $where.=' AND TO_DAYS(NOW()+ INTERVAL '.$defaultTime.' HOUR)= TO_DAYS(`time`+ INTERVAL '.$defaultTime.' HOUR)';
		}
		return $where;
	}
	
	/**
	 *	создание сводной таблицы
	 *
	 *	@param		string	$table			название таблицы
	 *	@param		string	$where			условия выборки
	 *	@param		array	$users			таблица перекодировки имен
	 */
	function showSummaryData($table, $where, $users)
	{
		//repo
		echo '<h3>Репо</h3>';
		$sqlStr='
		SELECT * FROM
		(
			SELECT `a_name`,  `repo`, count(`repo`) as count_commits FROM '.$table.' '.$where.' GROUP BY `repo`, `a_name`
			UNION ALL
			SELECT "", `repo`,  count(`repo`) as count_commits FROM '.$table.' '.$where.' GROUP BY `repo`) as w
		ORDER BY `repo`, `a_name`';
		$sql=mysql_query($sqlStr);
		$dataSumPrev['repo']=''; 
		$str='';
		while ($dataSum = mysql_fetch_array($sql)) 
		{
			if (array_key_exists($dataSum['a_name'], $users)) $dataSum['a_name']=$users[$dataSum['a_name']];
			$newRepo=false;
			if ($dataSum['repo']!=$dataSumPrev['repo']) $newRepo=true;
			if ($newRepo && $str!='') $str.= '</table><br>';
			if ($newRepo) $str.=  '<table id="customers"><tr><th>'.$dataSum['repo'].'</th><th>Коммитов ('.$dataSum['count_commits'].')</th>
									<th>Строк добавлено (из них пустых)</th><th>Строк удалено (из них пустых)</th><th>Файлов изменено</th><th>Файлов добавлено</th><th>Файлов удалено</th>';
			if (!$newRepo) 
			{
				$str.=  '<tr><td>'.$dataSum['repo'].' / '.$dataSum['a_name'].'</td><td>'.$dataSum['count_commits'].'</td>';	
				//get hash
				$sqlStr2='SELECT `hash` FROM '.$table.' '.$where.' AND `repo`="'.$dataSum['repo'].'" AND `a_name`="'.$dataSum['a_name'].'"';
				$sql2=mysql_query($sqlStr2);
				$gitLogParamAll=array(0,0,0,0,0,0,0);
				while ($dataHash = mysql_fetch_array($sql2)) 
				{
					
					$gitLogParam=getGitLogParam($dataHash['hash'],$dataSum['repo']);	
					$gitLogParamAll[0]+=$gitLogParam[0];
					$gitLogParamAll[1]+=$gitLogParam[1];
					$gitLogParamAll[2]+=$gitLogParam[2];
					$gitLogParamAll[3]+=$gitLogParam[3];
					$gitLogParamAll[4]+=$gitLogParam[4];
					$gitLogParamAll[5]+=$gitLogParam[5];
					$gitLogParamAll[6]+=$gitLogParam[6];
				}
				$str.=  '<td>'.$gitLogParamAll[0].' ('.$gitLogParamAll[2].')</td>';	
				$str.=  '<td>'.$gitLogParamAll[1].' ('.$gitLogParamAll[3].')</td>';	
				$str.=  '<td>'.$gitLogParamAll[6].'</td>';	
				$str.=  '<td>'.$gitLogParamAll[4].'</td>';	
				$str.=  '<td>'.$gitLogParamAll[5].'</td>';	
			}
			$dataSumPrev['repo']=$dataSum['repo'];
		}
		echo $str.'</table><br>';
		
		//users
		echo '<h3>Исполнители</h3>';
		$sqlStr='
		SELECT * FROM
		(
			SELECT `a_name`, `repo`, count(`a_name`) as count_commits FROM '.$table.' '.$where.' GROUP BY `a_name`, `repo`
			UNION ALL
			SELECT `a_name`, "", count(`a_name`) as count_commits FROM '.$table.' '.$where.' GROUP BY `a_name`) as w
		ORDER BY `a_name`, `repo`';
		$sql=mysql_query($sqlStr);
		$dataSumPrev['a_name']=''; 
		$str='';
		while ($dataSum = mysql_fetch_array($sql)) 
		{
			if (array_key_exists($dataSum['a_name'], $users)) $dataSum['a_name']=$users[$dataSum['a_name']];
			$newUser=false;
			if ($dataSum['a_name']!=$dataSumPrev['a_name']) $newUser=true;
			if ($newUser && $str!='') $str.= '</table><br>';
			if ($newUser) $str.=  '<table id="customers"><tr><th>'.$dataSum['a_name'].'</th><th>Коммитов ('.$dataSum['count_commits'].')</th>
									<th>Строк добавлено (из них пустых)</th><th>Строк удалено (из них пустых)</th><th>Файлов изменено</th><th>Файлов добавлено</th><th>Файлов удалено</th>';
			if (!$newUser) 
			{
				$str.=  '<tr><td>'.$dataSum['a_name'].' / '.$dataSum['repo'].'</td><td>'.$dataSum['count_commits'].'</td>';	
				//get hash
				$sqlStr2='SELECT `hash` FROM '.$table.' '.$where.' AND `repo`="'.$dataSum['repo'].'" AND `a_name`="'.$dataSum['a_name'].'"';
				$sql2=mysql_query($sqlStr2);
				$gitLogParamAll=array(0,0,0,0,0,0,0);
				while ($dataHash = mysql_fetch_array($sql2)) 
				{
					
					$gitLogParam=getGitLogParam($dataHash['hash'],$dataSum['repo']);	
					$gitLogParamAll[0]+=$gitLogParam[0];
					$gitLogParamAll[1]+=$gitLogParam[1];
					$gitLogParamAll[2]+=$gitLogParam[2];
					$gitLogParamAll[3]+=$gitLogParam[3];
					$gitLogParamAll[4]+=$gitLogParam[4];
					$gitLogParamAll[5]+=$gitLogParam[5];
					$gitLogParamAll[6]+=$gitLogParam[6];
				}
				$str.=  '<td>'.$gitLogParamAll[0].' ('.$gitLogParamAll[2].')</td>';	
				$str.=  '<td>'.$gitLogParamAll[1].' ('.$gitLogParamAll[3].')</td>';	
				$str.=  '<td>'.$gitLogParamAll[6].'</td>';	
				$str.=  '<td>'.$gitLogParamAll[4].'</td>';	
				$str.=  '<td>'.$gitLogParamAll[5].'</td>';	
			}
			$dataSumPrev['a_name']=$dataSum['a_name'];
		}
		echo $str.'</table><br>';
	}
	
	/**
	 *	Возвращает необходимые данные из git log
	 *
	 *	@param		string	$hash			hash	
	 *	@param		string	$repo			repo	
	 */
	function getGitLogParam($hash, $repo)
	{
		$resPull=exec("cd /home/repos/$repo && git log -p $hash -n 1 --stat",$outputPull,$returnPull);
		
		/*
		for ($i=0; $i<count($outputPull); $i++)
		{
			//$outputPull[$i]=htmlspecialchars($outputPull[$i]);
			//echo '<br>'.$outputPull[$i];
		}
		*/
		$addStr=0;
		$delStr=0;
		$addEmptyStr=0;
		$delEmptyStr=0;
		$addFile=0;
		$delFile=0;
		$modFile=0;
		for ($i=0; $i<count($outputPull); $i++)
		{
			//добавленные строки
			if (preg_match("/^(\\+).[^\\+]/",$outputPull[$i],$out_arr)) $addStr++;
			//пустые
			if (preg_match("/^(\\+)$/",$outputPull[$i],$out_arr)) $addEmptyStr++;
			//удаленные строки
			if (preg_match("/^(\\-).[^\\-]/",$outputPull[$i],$out_arr)) $delStr++;
			//пустые
			if (preg_match("/^(\\-)$/",$outputPull[$i],$out_arr)) $delEmptyStr++;
		}

		$resPull2=exec("cd /home/repos/$repo && git log -p $hash -n 1 --name-status",$outputPull2,$returnPull2);
		for ($i=0; $i<count($outputPull2); $i++)
		{
			//добавлено, удаление, изменение файлов
			if (preg_match("/^A\s/",$outputPull2[$i],$out_arr)) $addFile++;
			if (preg_match("/^D\s/",$outputPull2[$i],$out_arr)) $delFile++;
			if (preg_match("/^M\s/",$outputPull2[$i],$out_arr)) $modFile++;
			
		
		}
		return array($addStr + $addEmptyStr, $delStr + $delEmptyStr, $addEmptyStr, $delEmptyStr, $addFile, $delFile, $modFile);
	}
	
	/**
	 *	Выводит шапку таблицы с 3мя фильтрами: период, коммитер и репо
	 *
	 *	@param		string	$table			название таблицы
	 */
	function createFilter($table)
	{
		//select time
		echo '<tr><th><select name="time">';
			$flag=$_POST['time']=='all_time' ? 'selected' : '';
			echo '<option '.$flag.' value=all_time>all_time</option>';
			$flag=($_POST['time']=='today' || (!isset($_POST['time']))) ? 'selected' : '';
			echo '<option '.$flag.' value=today>today</option>';
			$flag=$_POST['time']=='yesterday' ? 'selected' : '';
			echo '<option '.$flag.' value=yesterday>yesterday</option>';
			$flag=$_POST['time']=='week' ? 'selected' : '';
			echo '<option '.$flag.' value=week>week</option>';
			$flag=$_POST['time']=='month' ? 'selected' : '';
			echo '<option '.$flag.' value=month>month</option>';
		echo '</select></th>';
		
		//select autor
		$sqlStr='SELECT DISTINCT `a_name` FROM '.$table.' ORDER BY `a_name`';
		$sql=mysql_query($sqlStr);
		$flag='';
		if (!isset($_POST['name']) || $_POST['name'][0]=='all_name') $flag= 'selected';
		echo '<th><select name="name[]"  size="10" multiple>
				<option '.$flag.' value=all_name>all_name</option>';
		
		while ($aName = mysql_fetch_array($sql)) 
		{
			$flag='';
			if (isset($_POST['name'])) 
			{
				for ($i=0; $i<count($_POST['name']); $i++)	if ($_POST['name'][$i]==$aName['a_name']) $flag= 'selected';
			}
			echo'<option '.$flag.' value="'.$aName['a_name'].'" >'.$aName['a_name'].'</option>';
		}
		echo '</select></th>';
		//select repo
		$sqlStr='SELECT DISTINCT `repo` FROM '.$table.' ORDER BY `repo`';
		$sql=mysql_query($sqlStr);
		$flag='';
		if (!isset($_POST['repo']) || $_POST['repo'][0]=='all_repo') $flag= 'selected';
		echo '<th><select name="repo[]"  size="10" multiple>
				<option '.$flag.' value=all_repo>all_repo</option>';
		
		while ($repo = mysql_fetch_array($sql)) 
		{
			$flag='';
			if (isset($_POST['repo'])) 
			{
				for ($i=0; $i<count($_POST['repo']); $i++)	if ($_POST['repo'][$i]==$repo['repo']) $flag= 'selected';
			}
			echo'<option '.$flag.' value="'.$repo['repo'].'" >'.$repo['repo'].'</option>';
		}
		echo '</select></th><th><input type="submit" value="Посмотреть"></th>';
	}	
	
	/**
	 *	получение данных и вывод в таблицу
	 *
	 *	@param		string	$table			название таблицы
	 *	@param		string	$where			условия выборки
	 *	@param		int		$countRows		количество строк на странице
	 *	@param		int		$page			номер страницы
	 *	@param		array	$users			таблица перекодировки имен
	 */
	function showData($table, $where, $countRows, $page, $users)
	{
		$sqlStr='SELECT * FROM '.$table.' '.$where.' ORDER BY `time` DESC LIMIT '.($countRows*$page).', '.$countRows;
		$sql=mysql_query($sqlStr);
		
		$dataAll=array();
		while ($data = mysql_fetch_array($sql)) 
		{
			$dataAll[]=$data;
		}
		
		for ($i=0; $i<count($dataAll); $i++)
		{
			if (array_key_exists($dataAll[$i]['a_name'], $users)) $dataAll[$i]['a_name']=$users[$dataAll[$i]['a_name']];
			
			$gitLogParam=getGitLogParam($dataAll[$i]['hash'],$dataAll[$i]['repo']);	
				
			echo'<tr>
				<td>'.$dataAll[$i]['time'].'</td>
				<td>'.$dataAll[$i]['a_name'].'</td>
				<td>'.$dataAll[$i]['repo'].'</td>
				<td>'.$dataAll[$i]['a_mail'].'</td>
				<td><a target="_blank" href="https://github.com/addflowinc/'.$dataAll[$i]['repo'].'/commit/'.$dataAll[$i]['hash'].'">'.$dataAll[$i]['hash'].'</a></td>
				<td>'.$dataAll[$i]['status'].'</td>
				<td>'.$dataAll[$i]['ref'].'</td>
				<td><a target="_blank" href="https://github.com/addflowinc/'.$dataAll[$i]['repo'].'/commit/'.$dataAll[$i]['hash'].'">'.$dataAll[$i]['message'].'</a></td>
				<td>'.$gitLogParam[0].'</td>
				<td>'.$gitLogParam[1].'</td>
				<td>'.$gitLogParam[6].'</td>
				<td>'.$gitLogParam[4].'</td>
				<td>'.$gitLogParam[5].'</td>
				</tr>';	
		}
	}
	
	/**
	 *	кнопочки Prev и Next
	 *
	 *	@param		int		$numRows		количество строк в выборке всего
	 *	@param		int		$countRows		количество строк на странице
	 *	@param		int		$page			номер страницы
	 */
	function createPrevNext($numRows, $countRows, $page)
	{
		if ($page > 0) {
			$p=$page-1;
			echo "<a href=ad.php?page=$p>prev</a>&nbsp";
		}
		$page++;                          
		if ($countRows*$page < $numRows) echo "<a href=ad.php?page=$page>next</a>";
	}
	
	





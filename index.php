<?php
$data = isset($_POST['data'])? $_POST['data']: null;
if ($data != null) {
	$param = json_decode($data);
	$result = array
		(
			'message'=>'',
			'size'=>'unknow',
			'version'=>'unknow',
			'url' => array(''),
			'success'=>true
		);
	$branchs = array("Stable", "Beta", "Dev", "Canary");
	$archs = array("x86", "x64");
	if (array_key_exists("branch", $param) && in_array($param->branch, $branchs)) {
		$result['message'] .= "branch [". $param->branch ."].";
	} else {
		$result['message'] .= "error occur in branch.";
		$result['success'] = false;
	}
	if (array_key_exists("arch", $param) && in_array($param->arch, $archs)) {
		$result['message'] .= "arch [". $param->arch ."].";
	} else {
		$result['message'] .= "error occur in arch.";
		$result['success'] = false;
	}

	function curl_post($url, $post_data = '', $timeout = 5) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		if ($post_data != '') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$file_contents = curl_exec($ch);
		if (curl_errno($ch)) {
			$file_contents = curl_error($ch);
		}

		curl_close($ch);
		return $file_contents;
	}

	function update_checker($branch, $arch, &$result, $timeout = 3) {
		if (false == $result['success']) {
			return;
		}
		$result['success'] = false;

		$appid = array
		(
			'Stable'=>'4DC8B4CA-1BDA-483E-B5FA-D3C12E15B62D',
			'Beta'  =>'4DC8B4CA-1BDA-483E-B5FA-D3C12E15B62D',
			'Dev'   =>'4DC8B4CA-1BDA-483E-B5FA-D3C12E15B62D',
			'Canary'=>'4EA16AC7-FD5A-47C3-875B-DBF4A2008C20'
		);
		$ap = array
		(
			'Stable' => array('x86'=>'-multi-chrome', 'x64'=>'x64-stable-multi-chrome'),
			'Beta' => array('x86'=>'1.1-beta', 'x64'=>'x64-beta-multi-chrome'),
			'Dev' => array('x86'=>'2.0-dev', 'x64'=>'x64-dev-multi-chrome'),
			'Canary' => array('x86'=>'', 'x64'=>'x64-canary')
		);

		$updatecheck = "<?xml version='1.0' encoding='UTF-8'?><request protocol='3.0' ismachine='0'><hw sse='1' sse2='1' sse3='1' ssse3='1' sse41='1' sse42='1' avx='1' physmemory='16777216' /><os platform='win' version='6.3' arch='x64'/><app appid='{".$appid[$branch]."}' ap='".$ap[$branch][$arch]."'><updatecheck/></app></request>";

		try {
			$xml = curl_post("http://tools.google.com/service/update2", $updatecheck, $timeout);
			$simplexml = simplexml_load_string($xml);

			foreach ($simplexml->app->updatecheck->manifest->actions->action as $version) {
				if (null != $version['Version']) {
					$result['version'] = (string)$version['Version'];
					$result['success'] = true;
					break;
				}
			}

			if (true == $result['success']) {
				$result['size'] = (string)$simplexml->app->updatecheck->manifest->packages->package[0]['size'];
				$urls = $simplexml->app->updatecheck->urls;
				$filename = $simplexml->app->updatecheck->manifest->packages->package[0]['name'];
				for ($i = 0; $i < count($urls->url); $i++) {
					$result['url'][$i] = $urls->url[$i]['codebase'] . $filename;
				}
			} else {
				$result['message'] = $xml;
			}

			// $result['version'] = $simplexml->app->updatecheck->manifest->actions->action[1]['Version'];
			// for ($i = 0; $i < count($simplexml->app->updatecheck->urls->url); $i++) {
				// $result['url'][$i] = $simplexml->app->updatecheck->urls->url[$i]['codebase'];
			// }
			// foreach ($simplexml->app->updatecheck->urls->url as $url) {
				// echo $url['codebase'], "\n";
			// }
		} catch (Exception $e) {
			print $e->getMessage();
		}
	}

	update_checker($param->branch, $param->arch, $result);

	echo json_encode($result, JSON_UNESCAPED_SLASHES);
	exit();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>获取Chrome下载地址 mod by 「有事燒紙」</title>
		<link href="res/chrome.css" rel="stylesheet">
		<script src="res/chrome.js"></script>
	</head>

	<body spellcheck="false">

	<div class="container">

		<div class="title">
			<h1 class="masthead-brand">获取Chrome下载地址<sup>php</sup></h1>
		</div>

		<div class="inner">
		<table>
			<tr>
			<td>
			<label>1、选分支：</label>
			<select id="branch">
				<option value="Stable">正式版</option>
				<option value="Beta">测试版</option>
				<option value="Dev" selected>开发版</option>
				<option value="Canary">金丝雀</option>
			</select>
			</td>
			<td class="pad">
			</td>
			<td>
			<label>2、选架构：</label>
			<select id="arch">
				<option value="x86">32位</option>
				<option value="x64" selected>64位</option>
			</select>
			</td>
			<td class="pad">
			</td>
			<td>
			3、<a href="#" class="btn btn-default" id="query" onclick="javascript:query()">查询</a>
			</td>
			</tr>
		</table>

		<div id="content" style="display:none">
			<div>最新版本：<code><span id="version"></span></code>，文件大小：<code><span id="size"></span></code>。
			<p>下载链接：</p>
			<div class="highlight"><pre><code id="url"></code></pre></div>
			</div>
		</div>
		</div>

		<div class="footer">
		<p class="footer">
			<a href="https://github.com/cmheia/getchrome" target="_blank" class="extlink">PHP</a> version by<a href="https://blog.dabria.net" target="_blank" class="extlink">「有事燒紙」</a>
		</p>
		<p class="footer">
			Origin python by <a href="http://www.shuax.com" target="_blank" class="extlink">shuax</a>
		</p>
		</div>

		</div>

	</body>
</html>

function getXMLHttpRequest()
{
	var http_request = false;
	if (window.XMLHttpRequest)
	{
		http_request = new XMLHttpRequest();
		if (http_request.overrideMimeType)
		{
			http_request.overrideMimeType('text/xml');
		}
	}
	else if (window.ActiveXObject)
	{
		try
		{
			http_request = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch (e)
		{
			try
			{
				http_request = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch (e)
			{}

		}
	}
	if (!http_request)
	{
		alert('Giving up :( Cannot create an XMLHTTP instance');
		return false;
	}
	return http_request;
}

function $(obj)
{
	return document.getElementById(obj);
}

function complete(version, size, url)
{
	$("version").innerHTML = version;
	$("size").innerHTML = (size/1024/1024).toFixed(2) + "MB";
	$("url").innerHTML = url;
	$("content").style.display = "";
}

function alertContents(http_request)
{
	if (http_request.readyState == 4)
	{
		if (http_request.status == 200)
		{
			var data = eval('(' + http_request.responseText + ')');
			if (data.success)
			{
				var urls = "";
				for (var i in data.url) {
					var url = data.url[i]
					urls += '<a href="' + url + '">' + url + '</a>\n'
				}
				complete(data.version, data.size, urls);
			}
			else
			{
				complete("查询失败", "", data.message);
			}
		}
	}
}

var querying = false;

function query() {

	if (querying) return;

	var branch = $("branch").value;
	var arch = $("arch").value;
	if (!branch || !arch)
	{
		complete("?", "?", "请选择一个分支和一个架构");
		return;
	}

	$("content").style.display = "none";
	querying = true;
	http_request = getXMLHttpRequest();
	if (http_request)
	{
		http_request.onreadystatechange = function ()
		{
			alertContents(http_request);
			querying = false;
		};
		var url = window.location.href;
		http_request.open('POST', url, true);
		http_request.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		var data = "data="+encodeURIComponent(JSON.stringify({"branch":branch, "arch":arch}));
		// complete("?", "?", data);
		http_request.send(data);
	}
}

#coding=utf-8
import bottle
import json
import requests
import xml.etree.ElementTree as tree
import traceback

requests.packages.urllib3.disable_warnings()

@bottle.get('/getchrome')
def index():
    return bottle.static_file("index.html", ".")

@bottle.get('/chrome.js')
def index():
    return bottle.static_file("chrome.js", ".")

@bottle.post('/getchrome')
def query():
    data = bottle.request.forms.getunicode('data')
    data = json.loads(data)
    if "branch" not in data:
        return json.dumps({"success":False, "message":"branch error"})
    if data["branch"] not in ["Stable", "Beta", "Dev", "Canary"]:
        return json.dumps({"success":False, "message":"branch error"})
    if "arch" not in data:
        return json.dumps({"success":False, "message":"arch error"})
    if data["arch"] not in ["x86", "x64"]:
        return json.dumps({"success":False, "message":"arch error"})
    
    try:
        branch = data["branch"]
        arch = data["arch"]
        appid = {"Stable":"4DC8B4CA-1BDA-483E-B5FA-D3C12E15B62D", "Beta":"4DC8B4CA-1BDA-483E-B5FA-D3C12E15B62D", "Dev":"4DC8B4CA-1BDA-483E-B5FA-D3C12E15B62D", "Canary":"4EA16AC7-FD5A-47C3-875B-DBF4A2008C20"}
        ap = {"Stable":{"x86":"-multi-chrome", "x64":"x64-stable-multi-chrome"}, "Beta":{"x86":"1.1-beta", "x64":"x64-beta-multi-chrome"}, "Dev":{"x86":"2.0-dev", "x64":"x64-dev-multi-chrome"}, "Canary":{"x86":"", "x64":"x64-canary"}}

        xml = "<?xml version='1.0' encoding='UTF-8'?><request protocol='3.0' ismachine='0'><os platform='win' version='6.3' arch='x64'/><app appid='{{{0}}}' ap='{1}'><updatecheck/></app></request>".format(appid[branch], ap[branch][arch])
        r = requests.post('https://tools.google.com/service/update2', data=xml, timeout=2)

        root = tree.fromstring(r.text)

        manifest_node = root.find('.//manifest')
        manifest_version = manifest_node.get('version')

        package_node = root.find('.//package')
        package_name = package_node.get('name')
        package_size = float(package_node.get('size'))

        url_nodes = root.findall('.//url')

        url_prefixes = []
        for node in url_nodes:
            url_prefixes.append(node.get('codebase') + package_name)

        return json.dumps({"success":True, "url":url_prefixes, "version":manifest_version, "size":"{:0.2f}MB".format(package_size/1024/1024)})
    except Exception as e:
        print(e)
        traceback.print_exc()
        return json.dumps({"success":False, "message":"query error"})

bottle.run(host='0.0.0.0', port=80, server="tornado", reloader=True)
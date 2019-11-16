function getdata() {
    var form = new FormData();
    form.append("album",album);
    var xhr = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    xhr.open("post","photolist.php",true);
    xhr.onload = function(evt) {
        var response = evt.currentTarget.responseText;
        if (response == "") getdatafail();
        getdatashow(JSON.parse(response));
        // mdui.alert(response, '结果');
    }
    xhr.onerror = function(evt) {
        var response = evt.currentTarget.responseText;
        if (response == "") getdatafail();
        // mdui.alert(response, '失败');
    }
    xhr.send(form);
}
function getdatafail(info="") {
    mdui.snackbar({
        message: '与服务器的连接断开，正在重试。'+info,
        position: 'right-bottom'
    });
}
function argget(arg) {
    var z = new RegExp("(^|&)"+arg+"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(z);
    if (!r) return null;
    return decodeURI(r[2]);
}
function getdatashow(jsonarr) {
    const datainfo = jsonarr[0];
    if (datainfo[1] == oldmd5) return;
    if (reloadtime == 0) reloadtime = datainfo[2];
    oldmd5 = datainfo[1];
    albumdir = datainfo[3];
    extname = supportwebp ? "webp" : "jpg";
    indexpic = albumdir+"/index."+extname;
    document.getElementById("indexpic").src = indexpic;
    photodata = jsonarr[1];
    var tabbarhtml = "";
    var dataarri = 0;
    for(var key in photodata){
        dataarri++;
        const nowdate = key.split(".");
        tabbarhtml += '<a href="#datetab'+dataarri+'-content" class="mdui-ripple mdui-ripple-white" id="datetab'+dataarri+'" value="'+key+'">'+parseInt(nowdate[0])+'&nbsp;月&nbsp;'+parseInt(nowdate[1])+'&nbsp;日</a>';
    }
    tabbardom.innerHTML = tabbarhtml;
    tabbarobj = new mdui.Tab(tabbardom);
    tabbarobj.show(selecttab);
}

let album = argget("album");
var tabbardom = document.getElementById("tabbar");
var tabbarobj = new mdui.Tab(tabbardom);
var oldmd5 = "";
var photodata = "";
var albumdir = "";
var reloadtime = 0;
var nowselecttab = 0;
var supportwebp = true;
var selecttab = 0;
getdata();

try {
    document.createElement('canvas').toDataURL('image/webp', 0.5).indexOf('data:image/webp') === 0;
} catch(err) {
    supportwebp = false;
}

tabbardom.addEventListener("change.mdui.tab", function (event) {
    const selectindex = event._detail.index + 1;
    const selectdom = document.getElementById("datetab"+selectindex);
    const selectarr = photodata[selectdom.getAttribute("value")];
    var photoshtml = "";
    for(var hkey in selectarr){
        const subtext = hkey+":00&nbsp;-&nbsp;"+hkey+":59";
        photoshtml += '<p><span id="timelinedot"></span><span id="timelinetime">'+subtext+'</span></p>';
        const photounit = selectarr[hkey];
        for (photoitem of photounit) {
            var extname = photoitem[1];
            if ((extname == "jpg" || extname == "png") && supportwebp) extname = "webp";
            const fileurl = albumdir+album+"/"+photoitem[0]+".S."+extname;
            var filedate = photoitem[2];
            filedate = parseInt(filedate[0])+"&nbsp;年&nbsp;"+parseInt(filedate[1])+"&nbsp;月&nbsp;"+parseInt(filedate[2])+"&nbsp;日&emsp;"+parseInt(filedate[3])+"&nbsp;时&nbsp;"+parseInt(filedate[4])+"&nbsp;分"; //+filedate[5]+"&nbsp;秒";
            photoshtml += '<img class="photo mdui-shadow-2 mdui-hoverable" id="photo'+photoitem[0]+'" src="'+fileurl+'" mdui-tooltip="{content: \''+filedate+'\'}" />';
        }
    }
    document.getElementById("timelinebg").innerHTML = photoshtml;
});


function showInfo(imageDir, info) {
    document.getElementById("hideOnHover").style.display = "none";
    var htmlString = "<small>" + info.sessiondate + "</small><br><a href='" + info.attendanceurllong + "'><small>" +
        "<span>" + info.attendancename + "</span></a>&nbsp;-&nbsp;<span>" + info.attendance + "</span></small>&nbsp;" +
        "<img class='icon iconInInfo' alt='" + info.attendance + "' title='" + info.attendance + "' src='" + imageDir + info.attendanceenglish + ".gif'>";
    document.getElementById("attendanceInfoBox").innerHTML = htmlString;
    document.getElementById("attendanceInfoBox").style.display = "block";
}

function onClick(url) {
    window.location.href = '../' + url;
}
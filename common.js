function setDisplay(id, val)
{
	document.getElementById(id).style.display = val?'':'none';
}

/* getAbsX,Y and tipon,off kindly borrowed from USOSweb */
function getAbsX(obj)
{
	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

function getAbsY(obj)
{
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	return curtop;
}

function tipon(parent,tip,width,y)
{
	var y = typeof(y) != 'undefined' ? y : 18;
	var tooltip = document.getElementById("tooltip");
	tooltip.closing = false;
	tooltip.style.width = width+"px";
	tooltip.style.top = getAbsY(parent)+y+"px";
	var x = getAbsX(parent);
	var clientWidth = document.body.clientWidth;
	if (x > (clientWidth-(width+30)))
		x = x - width;
	
	tooltip.style.left = x+"px";
	tooltip.innerHTML = tip;
	$('#tooltip').stop(true,true).show();
}

function tipoff()
{
	$("#tooltip").fadeOut('slow');
}

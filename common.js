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
	// TODO Check if parameters width,y are used at all (remove them)
	var y = typeof(y) != 'undefined' ? y : 10;
	var tooltip = document.getElementById("tooltip");
	tooltip.closing = false;
	tooltip.style.width = width+"px";
	tooltip.style.top = getAbsY(parent)+y+"px";
	
	tooltip.innerHTML = tip;
	
	// Move box left before measuring width, so that text isn't tightened.
	tooltip.style.left = 0 +"px";
	
	var width = typeof(width) != 'undefined' ? width : $('#tooltip').width();
	
	var x = getAbsX(parent) + 10;
	var clientWidth = document.body.clientWidth;
	if ((x+width+30) > clientWidth)
		x = clientWidth - width - 30;	
	tooltip.style.left = x+"px";
	// TODO: we could fix y too

	$('#tooltip').stop(true,true).show();
}

function tipoff()
{
	$("#tooltip").fadeOut('slow');
}

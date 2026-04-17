<!doctype html>
<html>
  <body>
    <canvas
      id="myCanvas"
      width="200"
      height="100"
      style="border: 1px solid #d3d3d3"
    >
      Your browser does not support the HTML canvas tag.</canvas
    >

    <script>
      var c = document.getElementById("myCanvas");
      var ctx = c.getContext("2d");

      //face
      ctx.beginPath();
      ctx.arc(100,50,40,0,2*Math.PI);
      ctx.stroke();

      //left ear
      ctx.beginPath();

      ctx.moveTo(55,5);

      ctx.lineTo(80,13);
      ctx.lineTo(60,35);
      ctx.lineTo(55,5);

      ctx.stroke();

      // right ear
      ctx.beginPath();

      ctx.moveTo(145,5);

      ctx.lineTo(120,13);
      ctx.lineTo(140,35);
      ctx.lineTo(145,5);

      ctx.stroke();

      // eyes
      ctx.beginPath();
      ctx.arc(85,45,5,0,2*Math.PI);
      ctx.stroke();
      ctx.beginPath();
      ctx.arc(115,45,5,0,2*Math.PI);
      ctx.stroke();

      //nose
      ctx.beginPath();
      ctx.moveTo(100,60);

      ctx.lineTo(95,55);
      ctx.lineTo(105,55);
      ctx.lineTo(100,60);

      //left whiskers
      ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(93,56);
      ctx.lineTo(65,53);

      ctx.moveTo(93,59);
      ctx.lineTo(65,58);

      ctx.moveTo(93,62);
      ctx.lineTo(65,63);

      //right whiskers
      ctx.moveTo(107,56);
      ctx.lineTo(135,53);

      ctx.moveTo(107,59);
      ctx.lineTo(135,58);

      ctx.moveTo(107,62);
      ctx.lineTo(135,63);

      //mouth
      ctx.moveTo(100,60);
      ctx.lineTo(90,70);

      ctx.moveTo(100,60);
      ctx.lineTo(110,70);

      ctx.stroke();
    </script>
  </body>
</html>

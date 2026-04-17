<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detailed Cat</title>
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #1a1820;
        font-family: "Segoe UI", sans-serif;
        color: #eee;
        padding: 1rem;
      }
      h1 {
        margin-bottom: 0.5rem;
        font-size: 1.2rem;
        font-weight: 600;
        color: #e8dcc8;
      }
      .canvas-wrap {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
      }
      #catCanvas {
        display: block;
        background: linear-gradient(
          180deg,
          #2a2535 0%,
          #1e1a28 50%,
          #252030 100%
        );
      }
    </style>
  </head>
  <body>
    <h1>Cat</h1>
    <div class="canvas-wrap">
      <canvas id="catCanvas" width="700" height="600"></canvas>
    </div>

    <script>
      (function () {
        const canvas = document.getElementById("catCanvas");
        const ctx = canvas.getContext("2d");
        const W = canvas.width;
        const H = canvas.height;

        // Center of cat (chest area) - cat faces slightly right
        const cx = W * 0.52;
        const cy = H * 0.55;
        const outline = "#1a1618";
        const lineW = 2.5;

        function drawCat() {
          ctx.save();

          // ---- Soft shadow under cat ----
          ctx.fillStyle = "rgba(0,0,0,0.35)";
          ctx.beginPath();
          ctx.ellipse(cx + 15, cy + 200, 140, 35, 0.08, 0, Math.PI * 2);
          ctx.fill();

          // ---- TAIL (curved, behind body - draw first so it goes under) ----
          ctx.strokeStyle = outline;
          ctx.lineWidth = lineW;
          ctx.lineCap = "round";
          ctx.lineJoin = "round";
          // Tail base
          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.ellipse(cx + 95, cy + 75, 22, 35, -0.3, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          // Tail curve (thick to thin)
          ctx.strokeStyle = "#c88a30";
          ctx.lineWidth = 28;
          ctx.beginPath();
          ctx.moveTo(cx + 100, cy + 60);
          ctx.quadraticCurveTo(cx + 180, cy - 20, cx + 200, cy + 120);
          ctx.stroke();
          ctx.strokeStyle = outline;
          ctx.lineWidth = lineW;
          ctx.stroke();
          // Tail tip (darker)
          ctx.fillStyle = "#a07020";
          ctx.beginPath();
          ctx.ellipse(cx + 198, cy + 118, 14, 22, 0.5, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          // Tail fur bands (subtle)
          ctx.strokeStyle = "rgba(160, 120, 50, 0.5)";
          ctx.lineWidth = 2;
          ctx.beginPath();
          ctx.moveTo(cx + 120, cy + 30);
          ctx.quadraticCurveTo(cx + 155, cy - 5, cx + 185, cy + 85);
          ctx.stroke();

          // ---- BACK & BODY ----
          ctx.fillStyle = "#b87828";
          ctx.strokeStyle = outline;
          ctx.lineWidth = lineW;
          ctx.beginPath();
          ctx.ellipse(cx + 30, cy + 50, 75, 95, 0.12, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          // Back highlight (sunlit)
          const backGrad = ctx.createLinearGradient(
            cx - 50,
            cy - 30,
            cx + 80,
            cy + 80,
          );
          backGrad.addColorStop(0, "rgba(230, 180, 100, 0.4)");
          backGrad.addColorStop(0.5, "rgba(200, 150, 70, 0.15)");
          backGrad.addColorStop(1, "transparent");
          ctx.fillStyle = backGrad;
          ctx.beginPath();
          ctx.ellipse(
            cx + 15,
            cy + 25,
            55,
            70,
            0.1,
            0.4 * Math.PI,
            1.2 * Math.PI,
          );
          ctx.fill();
          // Belly (lighter)
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.ellipse(cx - 25, cy + 85, 45, 55, 0.05, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#f0d890";
          ctx.beginPath();
          ctx.ellipse(
            cx - 28,
            cy + 90,
            32,
            40,
            0.05,
            0.2 * Math.PI,
            0.9 * Math.PI,
          );
          ctx.fill();
          ctx.stroke();

          // ---- HIND LEG (visible one) ----
          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.ellipse(cx + 75, cy + 155, 28, 55, -0.15, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#c88a30";
          ctx.beginPath();
          ctx.ellipse(cx + 78, cy + 195, 22, 28, -0.1, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          // Paw
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.ellipse(cx + 80, cy + 218, 26, 14, -0.1, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#d4a858";
          ctx.beginPath();
          ctx.arc(cx + 72, cy + 216, 5, 0, Math.PI * 2);
          ctx.arc(cx + 80, cy + 220, 5, 0, Math.PI * 2);
          ctx.arc(cx + 88, cy + 216, 5, 0, Math.PI * 2);
          ctx.fill();
          ctx.strokeStyle = outline;
          ctx.lineWidth = 1.5;
          ctx.stroke();

          // ---- FRONT LEGS (sitting pose) ----
          // Left front leg (our left)
          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.ellipse(cx - 35, cy + 120, 22, 65, 0.2, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#c88a30";
          ctx.beginPath();
          ctx.ellipse(cx - 38, cy + 175, 18, 22, 0.15, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.ellipse(cx - 40, cy + 192, 20, 12, 0.1, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#d4a858";
          ctx.beginPath();
          ctx.arc(cx - 48, cy + 190, 4, 0, Math.PI * 2);
          ctx.arc(cx - 40, cy + 194, 4, 0, Math.PI * 2);
          ctx.arc(cx - 32, cy + 190, 4, 0, Math.PI * 2);
          ctx.fill();
          ctx.strokeStyle = outline;
          ctx.lineWidth = 1.5;
          ctx.stroke();

          // Right front leg (paw forward)
          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.ellipse(cx + 25, cy + 118, 24, 68, -0.1, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#c88a30";
          ctx.beginPath();
          ctx.ellipse(cx + 28, cy + 178, 20, 24, -0.05, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.ellipse(cx + 32, cy + 198, 22, 13, -0.05, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#d4a858";
          ctx.beginPath();
          ctx.arc(cx + 24, cy + 196, 4.5, 0, Math.PI * 2);
          ctx.arc(cx + 32, cy + 200, 4.5, 0, Math.PI * 2);
          ctx.arc(cx + 40, cy + 196, 4.5, 0, Math.PI * 2);
          ctx.fill();
          ctx.strokeStyle = outline;
          ctx.lineWidth = 1.5;
          ctx.stroke();

          // ---- NECK & CHEST ----
          ctx.fillStyle = "#c88a30";
          ctx.beginPath();
          ctx.ellipse(cx - 5, cy + 15, 42, 50, 0.08, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.ellipse(
            cx - 12,
            cy + 35,
            28,
            38,
            0.05,
            0.15 * Math.PI,
            0.85 * Math.PI,
          );
          ctx.fill();
          ctx.stroke();

          // ---- HEAD ----
          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.arc(cx + 5, cy - 45, 72, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#c88a30";
          ctx.beginPath();
          ctx.arc(cx + 8, cy - 48, 58, 0.25 * Math.PI, 1.75 * Math.PI);
          ctx.fill();
          ctx.stroke();

          // Cheek / whisker pad (slightly lighter)
          ctx.fillStyle = "#d49848";
          ctx.beginPath();
          ctx.ellipse(cx - 35, cy - 35, 22, 18, -0.2, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.beginPath();
          ctx.ellipse(cx + 42, cy - 42, 20, 16, 0.15, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();

          // ---- EARS ----
          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.moveTo(cx - 55, cy - 95);
          ctx.lineTo(cx - 18, cy - 118);
          ctx.lineTo(cx + 12, cy - 98);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.moveTo(cx - 48, cy - 92);
          ctx.lineTo(cx - 22, cy - 108);
          ctx.lineTo(cx + 5, cy - 94);
          ctx.closePath();
          ctx.fill();
          ctx.strokeStyle = outline;
          ctx.lineWidth = lineW;
          ctx.stroke();

          ctx.fillStyle = "#b87828";
          ctx.beginPath();
          ctx.moveTo(cx + 38, cy - 100);
          ctx.lineTo(cx + 72, cy - 120);
          ctx.lineTo(cx + 68, cy - 82);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#e8c878";
          ctx.beginPath();
          ctx.moveTo(cx + 44, cy - 97);
          ctx.lineTo(cx + 65, cy - 112);
          ctx.lineTo(cx + 62, cy - 86);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();

          // Ear tufts (small)
          ctx.fillStyle = "#f0d890";
          ctx.beginPath();
          ctx.moveTo(cx - 50, cy - 96);
          ctx.lineTo(cx - 45, cy - 102);
          ctx.lineTo(cx - 40, cy - 97);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();
          ctx.beginPath();
          ctx.moveTo(cx + 65, cy - 118);
          ctx.lineTo(cx + 68, cy - 124);
          ctx.lineTo(cx + 72, cy - 118);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();

          // ---- EYES ----
          // Eye whites (barely visible, almond)
          ctx.fillStyle = "rgba(255, 250, 240, 0.4)";
          ctx.beginPath();
          ctx.ellipse(cx - 22, cy - 52, 20, 14, -0.15, 0, Math.PI * 2);
          ctx.fill();
          ctx.beginPath();
          ctx.ellipse(cx + 28, cy - 55, 20, 14, 0.12, 0, Math.PI * 2);
          ctx.fill();
          // Iris (green-gold)
          const irisGrad = ctx.createRadialGradient(
            cx - 22,
            cy - 54,
            0,
            cx - 22,
            cy - 54,
            14,
          );
          irisGrad.addColorStop(0, "#608830");
          irisGrad.addColorStop(0.5, "#789840");
          irisGrad.addColorStop(0.85, "#506828");
          irisGrad.addColorStop(1, "#384818");
          ctx.fillStyle = irisGrad;
          ctx.beginPath();
          ctx.ellipse(cx - 22, cy - 52, 14, 10, -0.15, 0, Math.PI * 2);
          ctx.fill();
          ctx.strokeStyle = outline;
          ctx.lineWidth = 2;
          ctx.stroke();
          ctx.fillStyle = irisGrad;
          ctx.beginPath();
          ctx.ellipse(cx + 28, cy - 55, 14, 10, 0.12, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          // Pupils (vertical slit - calm cat)
          ctx.fillStyle = "#0a0a0c";
          ctx.beginPath();
          ctx.ellipse(cx - 22, cy - 52, 4, 9, -0.15, 0, Math.PI * 2);
          ctx.fill();
          ctx.beginPath();
          ctx.ellipse(cx + 28, cy - 55, 4, 9, 0.12, 0, Math.PI * 2);
          ctx.fill();
          // Eye highlights
          ctx.fillStyle = "rgba(255, 255, 255, 0.9)";
          ctx.beginPath();
          ctx.ellipse(cx - 26, cy - 56, 4, 5, -0.2, 0, Math.PI * 2);
          ctx.ellipse(cx - 18, cy - 50, 2, 3, -0.1, 0, Math.PI * 2);
          ctx.fill();
          ctx.beginPath();
          ctx.ellipse(cx + 24, cy - 59, 4, 5, 0.15, 0, Math.PI * 2);
          ctx.ellipse(cx + 32, cy - 52, 2, 3, 0.1, 0, Math.PI * 2);
          ctx.fill();

          // ---- NOSE ----
          ctx.fillStyle = "#c06868";
          ctx.strokeStyle = outline;
          ctx.lineWidth = 2;
          ctx.beginPath();
          ctx.moveTo(cx + 8, cy - 38);
          ctx.lineTo(cx + 5, cy - 30);
          ctx.lineTo(cx + 11, cy - 30);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();
          ctx.fillStyle = "#d07878";
          ctx.beginPath();
          ctx.moveTo(cx + 6, cy - 32);
          ctx.lineTo(cx + 8, cy - 30);
          ctx.lineTo(cx + 10, cy - 32);
          ctx.closePath();
          ctx.fill();

          // ---- MOUTH ----
          ctx.strokeStyle = "#8a5050";
          ctx.lineWidth = 1.5;
          ctx.lineCap = "round";
          ctx.beginPath();
          ctx.moveTo(cx + 8, cy - 30);
          ctx.lineTo(cx + 8, cy - 18);
          ctx.stroke();
          ctx.beginPath();
          ctx.moveTo(cx + 8, cy - 18);
          ctx.lineTo(cx + 2, cy - 12);
          ctx.moveTo(cx + 8, cy - 18);
          ctx.lineTo(cx + 14, cy - 12);
          ctx.stroke();

          // ---- WHISKERS (many, both sides) ----
          ctx.strokeStyle = "rgba(60, 50, 45, 0.9)";
          ctx.lineWidth = 1.2;
          ctx.lineCap = "round";
          const whiskerBasesL = [
            [cx - 32, cy - 38],
            [cx - 30, cy - 32],
            [cx - 28, cy - 26],
            [cx - 30, cy - 20],
          ];
          const whiskerBasesR = [
            [cx + 38, cy - 45],
            [cx + 40, cy - 38],
            [cx + 42, cy - 32],
            [cx + 40, cy - 26],
          ];
          whiskerBasesL.forEach((b, i) => {
            ctx.beginPath();
            ctx.moveTo(b[0], b[1]);
            ctx.lineTo(b[0] - 65 - i * 4, b[1] - 15 + i * 3);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(b[0], b[1]);
            ctx.lineTo(b[0] - 62 - i * 3, b[1] + 10 - i * 2);
            ctx.stroke();
          });
          whiskerBasesR.forEach((b, i) => {
            ctx.beginPath();
            ctx.moveTo(b[0], b[1]);
            ctx.lineTo(b[0] + 70 + i * 4, b[1] - 18 + i * 4);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(b[0], b[1]);
            ctx.lineTo(b[0] + 68 + i * 3, b[1] + 12 - i * 2);
            ctx.stroke();
          });

          // ---- FUR LINES (subtle texture on head and back) ----
          ctx.strokeStyle = "rgba(140, 100, 50, 0.25)";
          ctx.lineWidth = 1;
          ctx.lineCap = "round";
          for (let i = 0; i < 12; i++) {
            const a = (i / 12) * Math.PI * 0.6 + 0.2 * Math.PI;
            const r = 50 + (i % 3) * 8;
            ctx.beginPath();
            ctx.moveTo(cx + 5 + Math.cos(a) * 40, cy - 45 + Math.sin(a) * 40);
            ctx.lineTo(cx + 5 + Math.cos(a) * r, cy - 45 + Math.sin(a) * r);
            ctx.stroke();
          }
          for (let i = 0; i < 8; i++) {
            ctx.beginPath();
            ctx.moveTo(cx + 20 + i * 12, cy + 30);
            ctx.lineTo(cx + 35 + i * 10, cy + 75);
            ctx.stroke();
          }

          ctx.restore();
        }

        drawCat();
      })();
    </script>
  </body>
</html>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>La Mia Million Dollar Page</title>
  <!-- Inclusione dell'SDK di PayPal (Ricordati di inserire il tuo Client ID) -->
  <script src="https://www.paypal.com/sdk/js?client-id=CLIENT_ID_PAYPAL&currency=EUR"></script>

  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      background: #f4f4f4;
      margin: 0;
      padding: 20px;
    }
    h1 {
      color: #333;
      margin-bottom: 5px;
    }
    p {
      color: #666;
      margin-bottom: 20px;
    }
    #grid-container {
      position: relative;
      display: inline-block;
      border: 2px solid #333;
      background: #fff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      user-select: none;
    }
    canvas {
      display: block;
      cursor: crosshair;
    }
    /* Stile della modale */
    #uploadModal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 25px;
      border-radius: 8px;
      border: 1px solid #ccc;
      box-shadow: 0 4px 20px rgba(0,0,0,0.25);
      z-index: 100;
      text-align: left;
      width: 340px;
    }
    #uploadModal h3 {
      margin-top: 0;
      color: #333;
    }
    #uploadModal label {
      display: block;
      margin-top: 10px;
      font-size: 13px;
      font-weight: bold;
      color: #444;
    }
    #uploadModal input[type="file"],
    #uploadModal input[type="url"],
    #uploadModal input[type="text"] {
      display: block;
      margin-top: 4px;
      margin-bottom: 12px;
      width: 100%;
      box-sizing: border-box;
      padding: 7px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .info-box {
      background: #e9ecef;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 12px;
      font-size: 14px;
    }
    .info-box span {
      font-weight: bold;
      color: #007bff;
    }
    #cancelBtn {
      background: #dc3545;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <h1>La mia Million Dollar Page</h1>
  <p>Trascina il mouse sulla griglia per selezionare il tuo spazio pubblicitario.</p>

  <div id="grid-container">
    <canvas id="pixelCanvas" width="1000" height="1000"></canvas>
  </div>

  <!-- Modale con Form e PayPal -->
  <div id="uploadModal">
    <h3>Acquista Spazio Pixel</h3>
    
    <div class="info-box">
      <div>Area: <span id="sizeInfo">0 px</span></div>
      <div>Totale da pagare: <span id="priceInfo">€ 0.00</span></div>
    </div>
    
    <form id="pixelForm">
      <input type="hidden" id="modalX">
      <input type="hidden" id="modalY">
      <input type="hidden" id="modalWidth">
      <input type="hidden" id="modalHeight">
      
      <label>Immagine (Banner):</label>
      <input type="file" id="imageFile" accept="image/*" required>
      
      <label>Link di destinazione (URL):</label>
      <input type="url" id="targetUrl" placeholder="https://example.com" required>
      
      <label>Titolo / Nome Attività (Tooltip):</label>
      <input type="text" id="pixelTitle" placeholder="Es. Mio Sponsor" required>
    </form>

    <!-- Contenitore del pulsante PayPal dinamico -->
    <div id="paypal-button-container" style="margin-top: 15px;"></div>

    <button type="button" id="cancelBtn" onclick="closeModal()">Annulla</button>
  </div>

  <script>
    const canvas = document.getElementById('pixelCanvas');
    const ctx = canvas.getContext('2d');
    const modal = document.getElementById('uploadModal');
    let pixelsList = [];

    let isDragging = false;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let calculatedPrice = 0;

    // Tooltip per i banner esistenti
    const tooltip = document.createElement('div');
    tooltip.style.position = 'absolute';
    tooltip.style.background = 'rgba(0, 0, 0, 0.85)';
    tooltip.style.color = '#fff';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.pointerEvents = 'none';
    tooltip.style.display = 'none';
    tooltip.style.zIndex = '50';
    document.body.appendChild(tooltip);

    async function loadPixels() {
      try {
        const response = await fetch('api.php');
        pixelsList = await response.json();
        drawGrid();
      } catch (err) {
        console.error('Errore nel caricamento:', err);
      }
    }

    function drawGrid(tempSelection = null) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = '#e5e5e5';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      pixelsList.forEach(pixel => {
        const img = new Image();
        img.src = pixel.imageUrl;
        img.onload = () => {
          ctx.drawImage(img, pixel.x, pixel.y, pixel.width, pixel.height);
        };
      });

      if (tempSelection) {
        ctx.fillStyle = 'rgba(0, 123, 255, 0.3)';
        ctx.strokeStyle = '#007bff';
        ctx.lineWidth = 2;
        ctx.fillRect(tempSelection.x, tempSelection.y, tempSelection.width, tempSelection.height);
        ctx.strokeRect(tempSelection.x, tempSelection.y, tempSelection.width, tempSelection.height);
      }
    }

    function getPixelAt(mouseX, mouseY) {
      for (let i = pixelsList.length - 1; i >= 0; i--) {
        const p = pixelsList[i];
        if (
          mouseX >= p.x && mouseX <= p.x + p.width &&
          mouseY >= p.y && mouseY <= p.y + p.height
        ) {
          return p;
        }
      }
      return null;
    }

    // --- GESTIONE DRAG AND DROP ---

    canvas.addEventListener('mousedown', (e) => {
      const rect = canvas.getBoundingClientRect();
      startX = Math.floor((e.clientX - rect.left) / 10) * 10;
      startY = Math.floor((e.clientY - rect.top) / 10) * 10;

      if (getPixelAt(startX, startY)) return; 
      isDragging = true;
    });

    canvas.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      const mouseX = e.clientX - rect.left;
      const mouseY = e.clientY - rect.top;

      if (isDragging) {
        currentX = Math.floor(mouseX / 10) * 10;
        currentY = Math.floor(mouseY / 10) * 10;

        let x = Math.min(startX, currentX);
        let y = Math.min(startY, currentY);
        let width = Math.max(10, Math.abs(currentX - startX) + 10);
        let height = Math.max(10, Math.abs(currentY - startY) + 10);

        drawGrid({ x, y, width, height });
        canvas.style.cursor = 'crosshair';
        tooltip.style.display = 'none';
      } else {
        const hoveredPixel = getPixelAt(mouseX, mouseY);
        if (hoveredPixel) {
          canvas.style.cursor = 'pointer';
          tooltip.style.display = 'block';
          tooltip.style.left = `${e.pageX + 12}px`;
          tooltip.style.top = `${e.pageY + 12}px`;
          tooltip.textContent = `${hoveredPixel.title} - ${hoveredPixel.targetUrl}`;
        } else {
          canvas.style.cursor = 'crosshair';
          tooltip.style.display = 'none';
        }
      }
    });

    canvas.addEventListener('mouseup', (e) => {
      if (!isDragging) {
        const rect = canvas.getBoundingClientRect();
        const clickedPixel = getPixelAt(e.clientX - rect.left, e.clientY - rect.top);
        if (clickedPixel) window.open(clickedPixel.targetUrl, '_blank');
        return;
      }

      isDragging = false;

      const rect = canvas.getBoundingClientRect();
      currentX = Math.floor((e.clientX - rect.left) / 10) * 10;
      currentY = Math.floor((e.clientY - rect.top) / 10) * 10;

      let x = Math.min(startX, currentX);
      let y = Math.min(startY, currentY);
      let width = Math.max(10, Math.abs(currentX - startX) + 10);
      let height = Math.max(10, Math.abs(currentY - startY) + 10);

      // --- CONTROLLO COLLISIONE PREVENTIVO (PRIMA DI APRIRE LA MODALE) ---
      let hasCollision = false;
      pixelsList.forEach(p => {
        let overlaps = (
          x < (p.x + p.width) &&
          (x + width) > p.x &&
          y < (p.y + p.height) &&
          (y + height) > p.y
        );
        if (overlaps) {
          hasCollision = true;
        }
      });

      if (hasCollision) {
        alert('Attenzione: l\'area selezionata contiene spazi già occupati da altri banner! Scegli un\'altra zona.');
        drawGrid();
        return; // Blocca tutto e non apre la modale
      }
      // -----------------------------------------------------------------

      let totalPixels = width * height;
      calculatedPrice = (totalPixels / 100).toFixed(2);

      document.getElementById('modalX').value = x;
      document.getElementById('modalY').value = y;
      document.getElementById('modalWidth').value = width;
      document.getElementById('modalHeight').value = height;
      document.getElementById('sizeInfo').textContent = `${width} x ${height} (${totalPixels} px)`;
      document.getElementById('priceInfo').textContent = `€ ${calculatedPrice}`;

      modal.style.display = 'block';
      renderPayPalButton();
      drawGrid();
    });

    canvas.addEventListener('mouseleave', () => {
      if (isDragging) {
        isDragging = false;
        drawGrid();
      }
      tooltip.style.display = 'none';
    });

    function closeModal() {
      modal.style.display = 'none';
      document.getElementById('pixelForm').reset();
      document.getElementById('paypal-button-container').innerHTML = '';
      drawGrid();
    }

    // --- RENDERIZZA IL PULSANTE PAYPAL DINAMICO ---
    function renderPayPalButton() {
      const container = document.getElementById('paypal-button-container');
      container.innerHTML = ''; 

      paypal.Buttons({
        onInit: function(data, actions) {
          actions.enable();
        },
        createOrder: function(data, actions) {
          const imageFile = document.getElementById('imageFile').files[0];
          const targetUrl = document.getElementById('targetUrl').value;
          const pixelTitle = document.getElementById('pixelTitle').value;

          if (!imageFile || !targetUrl || !pixelTitle) {
            alert('Compila tutti i campi e seleziona un\'immagine prima di pagare!');
            return actions.reject();
          }

          return actions.order.create({
            purchase_units: [{
              amount: {
                value: calculatedPrice
              },
              description: `Acquisto spazio pixel (${document.getElementById('sizeInfo').textContent})`
            }]
          });
        },
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            submitPixelToServer();
          });
        },
        onError: function(err) {
          console.error('Errore PayPal:', err);
          alert('Errore durante il processo di pagamento.');
        }
      }).render('#paypal-button-container');
    }

    // --- INVIO DATI AL SERVER PHP DOPO IL PAGAMENTO ---
    async function submitPixelToServer() {
      const formData = new FormData();
      formData.append('x', document.getElementById('modalX').value);
      formData.append('y', document.getElementById('modalY').value);
      formData.append('width', document.getElementById('modalWidth').value);
      formData.append('height', document.getElementById('modalHeight').value);
      formData.append('targetUrl', document.getElementById('targetUrl').value);
      formData.append('title', document.getElementById('pixelTitle').value);
      formData.append('image', document.getElementById('imageFile').files[0]);

      try {
        const response = await fetch('api.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          alert('Pagamento completato e banner pubblicato con successo!');
          closeModal();
          loadPixels();
        } else {
          alert('Errore nel salvataggio: ' + (result.error || 'Errore sconosciuto'));
        }
      } catch (err) {
        console.error('Errore di rete:', err);
        alert('Pagamento ricevuto ma c\'è stato un errore di comunicazione con il server.');
      }
    }

    loadPixels();
  </script>
</body>
</html>

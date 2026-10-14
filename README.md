# 💰 Million Dollar Homepage Clone

A modern, lightweight, and self-hosted clone of the famous **The Million Dollar Homepage**. Built with vanilla JavaScript, HTML5 Canvas, and a simple PHP backend, this project allows users to select pixel areas on a grid, upload banner images, set target links, and publish them instantly.

## ✨ Features

* **Interactive Grid (HTML5 Canvas):** High-performance $1000 \times 1000$ pixel grid.
* **Drag-and-Drop Selection:** Users can click and drag to select custom-sized advertising blocks (snapped to a 10px grid).
* **Collision Detection:** Prevents users from overlapping or overwriting already purchased blocks (checked both client-side and server-side).
* **Direct Image Uploads:** Handled securely via PHP, storing uploaded banners locally.
* **Interactive Tooltips & Links:** Hovering over a banner shows its title and URL; clicking it opens the link in a new tab.
* **Flexible Versions:** 
  * `indexpaypal.html` includes a dynamic **PayPal Checkout** SDK integration.
  * `indexfree.html` provides a direct-publish version without payments.

## 🚀 Getting Started

### Prerequisites
* A web server supporting **PHP** (e.g., Apache, Nginx, or built-in PHP development server).
* Write permissions enabled on the server directory so PHP can create `pixels.json` and the `uploads/` folder.

fetch("backend/api/get_product.php")
    .then(res => res.json())
    .then(data => {
        const div = document.getElementById("products");
        data.forEach(p => {
            div.innerHTML += `
        <div>
          <h3>${p.name}</h3>
          <p>Preis: ${p.price} €</p>
          <p>Stock: ${p.stock}</p>
        </div>
      `;
        });
    });
let cart = JSON.parse(localStorage.getItem("cart")) || [];

function addToCart(id, name, price) {
    cart.push({ id, name, price, qty: 1 });
    localStorage.setItem("cart", JSON.stringify(cart));
    alert("Added to cart");
}

fetch("backend/api/get_product.php")
    .then(r => r.json())
    .then(data => {
        const d = document.getElementById("products");
        data.forEach(p => {
            d.innerHTML += `
      <div class="product">
        <h3>${p.name}</h3>
        <p>${p.price} €</p>
        <button onclick="addToCart(${p.id},'${p.name}',${p.price})">
          Add to cart
        </button>
      </div>
    `;
        });
    });
fetch("backend/api/place_order.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        items: cart.map(c => ({
            product_id: c.id,
            qty: c.qty
        }))
    })
});
const lang = "ar"; // de / en / ar
fetch(`backend/api/get_product.php?lang=${lang}`)

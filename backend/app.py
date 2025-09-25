
import os, json
from datetime import date
from flask import Flask, jsonify, request, session, render_template, redirect, url_for
from flask_cors import CORS
from sqlalchemy import create_engine, Column, Integer, String, Float, Date
from sqlalchemy.orm import sessionmaker, declarative_base

APP_SECRET = os.environ.get("APP_SECRET", "dev_secret_change_me")
ADMIN_PASSWORD = os.environ.get("ADMIN_PASSWORD", "admin123")

app = Flask(__name__, static_url_path="/static", static_folder="static", template_folder="templates")
app.secret_key = APP_SECRET
CORS(app, supports_credentials=True)

engine = create_engine("sqlite:///products.db", echo=False, future=True)
Base = declarative_base()

class Product(Base):
    __tablename__ = "products"
    id = Column(Integer, primary_key=True, autoincrement=True)
    cat = Column(String(40), nullable=False)
    name_en = Column(String(120), nullable=False)
    name_ar = Column(String(120), nullable=False)
    name_de = Column(String(120), nullable=False)
    price = Column(Float, nullable=False)
    stock = Column(Integer, nullable=False)
    expiry = Column(Date, nullable=False)
    image = Column(String(200), nullable=False)

Base.metadata.create_all(engine)
SessionLocal = sessionmaker(bind=engine, future=True)

def seed_if_empty():
    with SessionLocal() as db:
        if db.query(Product).count() == 0:
            seed_path = os.path.join(os.path.dirname(__file__), "seed.json")
            with open(seed_path, "r", encoding="utf-8") as f:
                seed = json.load(f)
            for row in seed:
                db.add(Product(cat=row["cat"], name_en=row["name_en"], name_ar=row["name_ar"], name_de=row["name_de"],
                               price=row["price"], stock=row["stock"], expiry=date.fromisoformat(row["expiry"]), image=row["image"]))
            db.commit()
seed_if_empty()

def _cart():
    if "cart" not in session: session["cart"] = {}
    return session["cart"]

def _serialize(p, lang="ar"):
    name = p.name_ar if lang=="ar" else (p.name_de if lang=="de" else p.name_en)
    return {"id": p.id, "cat": p.cat, "name": name, "name_en": p.name_en, "name_ar": p.name_ar, "name_de": p.name_de,
            "price": p.price, "stock": p.stock, "expiry": p.expiry.isoformat(), "image": f"/static/images/{p.image}"}

@app.get("/api/products")
def list_products():
    lang = request.args.get("lang", "ar"); cat = request.args.get("cat"); q = request.args.get("q")
    with SessionLocal() as db:
        query = db.query(Product)
        if cat: query = query.filter(Product.cat==cat)
        items = query.all()
    out = [_serialize(p, lang) for p in items]
    if q:
        ql = q.lower()
        out = [x for x in out if ql in (x["name"] or "").lower() or ql in x["name_en"].lower() or ql in x["name_ar"].lower() or ql in x["name_de"].lower()]
    return jsonify(out)

@app.get("/api/products/<int:pid>")
def get_product(pid):
    lang = request.args.get("lang", "ar")
    with SessionLocal() as db:
        p = db.query(Product).get(pid)
        if not p: return jsonify({"error":"not_found"}), 404
        return jsonify(_serialize(p, lang))

@app.get("/api/cart")
def get_cart():
    cart = _cart(); line_items = []; subtotal = 0.0
    with SessionLocal() as db:
        for pid, qty in cart.items():
            p = db.query(Product).get(int(pid))
            if not p: continue
            line_total = p.price * qty; subtotal += line_total
            line_items.append({"id": p.id, "name": p.name_ar, "price": p.price, "qty": qty, "image": f"/static/images/{p.image}", "line_total": round(line_total,2)})
    tax = round(subtotal * 0.05, 2); delivery = 3.0 if subtotal < 25 else 0.0; total = round(subtotal + tax + delivery, 2)
    return jsonify({"items": line_items, "subtotal": round(subtotal,2), "tax": tax, "delivery_fee": delivery, "total": total})

@app.post("/api/cart/add")
def cart_add():
    data = request.get_json(force=True); pid = str(data.get("product_id")); qty = int(data.get("qty", 1))
    cart = _cart(); cart[pid] = cart.get(pid, 0) + qty; session["cart"] = cart; return jsonify({"ok": True})

@app.post("/api/cart/update")
def cart_update():
    data = request.get_json(force=True); pid = str(data.get("product_id")); qty = int(data.get("qty", 1))
    cart = _cart(); 
    if qty <= 0: cart.pop(pid, None)
    else: cart[pid] = qty
    session["cart"] = cart; return jsonify({"ok": True})

@app.post("/api/cart/checkout")
def checkout():
    data = request.get_json(force=True)
    session.pop("cart", None)
    return jsonify({"ok": True, "message": "Order placed", "method": data.get("method","cod"), "address": data.get("address"), "delivery_date": data.get("delivery_date")})

@app.get("/admin/login")
def admin_login_page():
    return render_template("admin_login.html")

@app.post("/admin/login")
def admin_login():
    from flask import request, redirect
    if request.form.get("password") == os.environ.get("ADMIN_PASSWORD", "admin123"):
        session["is_admin"] = True; return redirect(url_for("admin_panel"))
    return "Unauthorized", 401

@app.get("/admin/logout")
def admin_logout():
    session.pop("is_admin", None); 
    from flask import redirect
    return redirect(url_for("admin_login_page"))

@app.get("/admin")
def admin_panel():
    if not session.get("is_admin"):
        from flask import redirect
        return redirect(url_for("admin_login_page"))
    with SessionLocal() as db:
        items = db.query(Product).all()
    return render_template("admin.html", items=items)

@app.post("/admin/products")
def admin_create_or_update():
    if not session.get("is_admin"): return "Unauthorized", 401
    from flask import request, redirect
    form = request.form; pid = form.get("id")
    with SessionLocal() as db:
        if pid:
            p = db.query(Product).get(int(pid))
            if not p: return "Not found", 404
        else:
            p = Product()
        p.cat = form["cat"]; p.name_en = form["name_en"]; p.name_ar = form["name_ar"]; p.name_de = form["name_de"]
        p.price = float(form["price"]); p.stock = int(form["stock"]); p.expiry = date.fromisoformat(form["expiry"]); p.image = form["image"]
        if not pid: db.add(p)
        db.commit()
    return redirect(url_for("admin_panel"))

@app.post("/admin/products/<int:pid>/delete")
def admin_delete(pid):
    if not session.get("is_admin"): return "Unauthorized", 401
    with SessionLocal() as db:
        p = db.query(Product).get(pid)
        if p: db.delete(p); db.commit()
    from flask import redirect
    return redirect(url_for("admin_panel"))

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=7000, debug=True)

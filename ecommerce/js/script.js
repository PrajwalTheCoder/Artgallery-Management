// js/script.js (enhanced)
document.addEventListener('DOMContentLoaded', () => {
	const navToggle = document.getElementById('navToggle');
	const mainNav = document.getElementById('mainNav');
	if (navToggle && mainNav) {
		navToggle.addEventListener('click', () => {
			mainNav.classList.toggle('open');
		});
	}

	// Simple toast for add-to-cart if we detect a redirect back with param
	const params = new URLSearchParams(window.location.search);
	if (params.get('added')) {
		showToast('Added to cart');
	}

	// Intercept add-to-cart forms for AJAX
	document.querySelectorAll('form[action="add_to_cart.php"]').forEach(form => {
		form.addEventListener('submit', e => {
			e.preventDefault();
			const fd = new FormData(form);
			const btn = form.querySelector('button[type="submit"], .btn');
			if (btn){ btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent='Adding…'; }
			fetch('add_to_cart.php', {
				method:'POST',
				body: fd,
				headers: { 'X-Requested-With':'fetch' }
			}).then(async r=>{
				let data;
				try { data = await r.json(); } catch(err){ throw new Error('Invalid JSON'); }
				if (!r.ok) throw new Error(data.error || 'Server error');
				return data;
			}).then(data => {
				if (data.ok) {
					const cartCountEl = document.getElementById('cartCount');
					if (cartCountEl) cartCountEl.textContent = data.cartCount;
					showToast('Added to cart');
					showMiniCart();
				} else {
					showToast(data.error || 'Error adding');
				}
			}).catch(err=> showToast(err.message || 'Network error')).finally(()=>{
				if (btn){ btn.disabled=false; btn.textContent=btn.dataset.label || 'Add'; }
			});
		});
	});

	// Review form AJAX
	const reviewForm = document.getElementById('reviewForm');
	if (reviewForm){
		reviewForm.addEventListener('submit', e => {
			e.preventDefault();
			const msg = document.getElementById('reviewMsg');
			msg.textContent = 'Submitting...';
			const fd = new FormData(reviewForm);
			fetch('submit_review.php', {method:'POST', body:fd, headers:{'X-Requested-With':'fetch'}})
			.then(r=>r.json())
			.then(data=>{
				if(!data.ok){ msg.textContent = data.error || 'Error'; return; }
				msg.textContent = 'Review added';
				const list = document.getElementById('reviewList');
				const art = document.createElement('article');
				art.className='review-card';
				art.innerHTML = `<header><strong>${escapeHtml(fd.get('name'))}</strong><span class="stars">${'★'.repeat(fd.get('rating')) + '☆'.repeat(5-fd.get('rating'))}</span></header><p>${escapeHtml(fd.get('comment')).replace(/\n/g,'<br>')}</p><time>${data.review.created_at}</time>`;
				list.prepend(art);
				reviewForm.reset();
			})
			.catch(()=> msg.textContent='Network error');
		});
	}

	function escapeHtml(str){
		return String(str).replace(/[&<>"] /g, s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"," ":"&nbsp;"}[s]||s));
	}

	// Mini cart dropdown (simple placeholder using cart count only)
	let miniCart;
	function showMiniCart(){
		if(!miniCart){
			miniCart = document.createElement('div');
			miniCart.className='mini-cart';
			miniCart.innerHTML = '<div class="mc-inner"><strong>Item added</strong><p><a href="cart.php">View Cart</a> · <a href="#new" class="mc-close">Continue</a></p></div>';
			document.body.appendChild(miniCart);
			miniCart.addEventListener('click', e=>{ if(e.target.classList.contains('mc-close')) hideMiniCart(); });
		}
		miniCart.classList.add('on');
		clearTimeout(miniCart._t);
		miniCart._t = setTimeout(()=> hideMiniCart(), 4000);
	}
	function hideMiniCart(){ if(miniCart) miniCart.classList.remove('on'); }

	// Reviews tabs skeleton (static for now)
	const reviewTabs = document.querySelectorAll('[data-tab]');
	reviewTabs.forEach(tab => tab.addEventListener('click', () => {
		const target = tab.getAttribute('data-tab');
		document.querySelectorAll('[data-tabpanel]').forEach(p => p.hidden = p.getAttribute('data-tabpanel') !== target);
		reviewTabs.forEach(t=>t.classList.remove('active'));
		tab.classList.add('active');
	}));

	// Product detail dynamic total price
	const qtyInput = document.getElementById('qtyInput');
	const unitPriceEl = document.getElementById('unitPrice');
	const totalPriceEl = document.getElementById('totalPrice');
	if (qtyInput && unitPriceEl && totalPriceEl) {
		const unit = parseFloat(unitPriceEl.dataset.unit || '0');
		const readout = document.getElementById('qtyReadout');
		function fmt(n){ return new Intl.NumberFormat('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n); }
		function recalc(){
			let q = parseInt(qtyInput.value,10); if(isNaN(q)||q<1){ q=1; }
			qtyInput.value = q; // force sync
			const total = unit * q;
			totalPriceEl.innerHTML = 'Total: ₹ ' + fmt(total) + (readout ? ' <span class="qty-readout" id="qtyReadout" data-q="'+q+'">(Qty '+q+')</span>' : '');
		}
		['input','change'].forEach(ev=> qtyInput.addEventListener(ev, recalc));
		recalc();
	}

	// Generic plus/minus for any .qty-control
	document.querySelectorAll('.qty-control').forEach(ctrl => {
		const input = ctrl.querySelector('input[type="number"]');
		const decBtn = ctrl.querySelector('[data-dec]');
		const incBtn = ctrl.querySelector('[data-inc]');
		if(!input) return;

		let holdTimer=null, stepTimer=null, accelerating=false, stepDelay=320, step=1;

		function updateButtons(){
			const val = parseInt(input.value,10)||1;
			if (decBtn){
				if (val<=1){ decBtn.disabled = true; decBtn.classList.add('disabled'); }
				else { decBtn.disabled = false; decBtn.classList.remove('disabled'); }
			}
		}

		function bumpAnim(){
			input.classList.remove('qty-anim-bump');
			// force reflow
			void input.offsetWidth;
			input.classList.add('qty-anim-bump');
		}

		function change(delta){
			let val = parseInt(input.value,10)||1;
			val += delta;
			if (val<1) val=1;
			input.value = val;
			input.dispatchEvent(new Event('input',{bubbles:true}));
			updateButtons();
			bumpAnim();
		}

		function startHold(btn, delta){
			change(delta);
			clearTimeout(holdTimer); clearInterval(stepTimer);
			accelerating=false; stepDelay=320; step=1;
			holdTimer = setTimeout(()=>{
				accelerating=true;
				stepTimer = setInterval(()=>{
					change(delta);
					if (accelerating && stepDelay>60){ stepDelay = Math.max(60, stepDelay-30); clearInterval(stepTimer); stepTimer=setInterval(()=>change(delta), stepDelay); }
				}, stepDelay);
			}, 450); // long press threshold
		}

		function endHold(){ clearTimeout(holdTimer); clearInterval(stepTimer); accelerating=false; }

		ctrl.addEventListener('click', e => {
			const btn = e.target.closest('.qty-btn');
			if(!btn) return;
			if (btn.hasAttribute('data-inc')) change(1);
			else if (btn.hasAttribute('data-dec')) change(-1);
		});

		['mousedown','touchstart'].forEach(ev=>{
			ctrl.addEventListener(ev, e=>{
				const btn = e.target.closest('.qty-btn'); if(!btn || btn.disabled) return;
				startHold(btn, btn.hasAttribute('data-inc')?1:-1);
			});
		});
		['mouseup','mouseleave','touchend','touchcancel'].forEach(ev=>{
			ctrl.addEventListener(ev, endHold);
		});

		input.addEventListener('change', updateButtons);
		input.addEventListener('input', updateButtons);
		updateButtons();
	});

	function showToast(msg) {
		const t = document.createElement('div');
		t.className = 'toast';
		t.textContent = msg;
		document.body.appendChild(t);
		requestAnimationFrame(()=> t.classList.add('on'));
		setTimeout(()=> { t.classList.remove('on'); setTimeout(()=> t.remove(),400); }, 2300);
	}
});

// Basic CSS injection for toast if not present
const toastCSS = `.toast{position:fixed;bottom:24px;right:24px;background:#111;color:#fff;padding:.75rem 1rem;border-radius:8px;font-size:.8rem;opacity:0;transform:translateY(8px);transition:.35s ease;box-shadow:0 4px 14px -4px rgba(0,0,0,.4);z-index:1000}.toast.on{opacity:1;transform:translateY(0)}`;
const miniCartCSS = `.mini-cart{position:fixed;top:76px;right:24px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 10px 28px -8px rgba(0,0,0,.25);width:240px;max-width:90%;opacity:0;transform:translateY(-10px);transition:.35s ease;z-index:1000;font-size:.75rem}.mini-cart.on{opacity:1;transform:translateY(0)}.mini-cart .mc-inner{padding:1rem .9rem 1.05rem}.mini-cart a{color:#2563eb;font-weight:600}`;
if (!document.getElementById('toast-style')) {
	const s = document.createElement('style');
	s.id = 'toast-style';
	s.innerHTML = toastCSS + miniCartCSS;
	document.head.appendChild(s);
}

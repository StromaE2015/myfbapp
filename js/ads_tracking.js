function trackAdView(adId, userId) {
    fetch("assist/track_ad_interaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "ad_id=" + encodeURIComponent(adId) + "&user_id=" + encodeURIComponent(userId) + "&interaction_type=view"
    });
}

function trackAdInteraction(adId, userId, interactionType) {
    fetch("assist/track_ad_interaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "ad_id=" + encodeURIComponent(adId) + "&user_id=" + encodeURIComponent(userId) + "&interaction_type=" + encodeURIComponent(interactionType)
    });
}

function trackAdClick(adId, userId) {
    trackAdInteraction(adId, userId, "click");
}



document.querySelectorAll('.ad-card').forEach(ad => observer.observe(ad));

function trackInteraction(itemId, itemType, interactionType) {
    fetch("assist/track_interaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "item_id=" + encodeURIComponent(itemId) +
              "&item_type=" + encodeURIComponent(itemType) +
              "&interaction_type=" + encodeURIComponent(interactionType)
    });
}

function trackPostClick(postId) {
    trackInteraction(postId, "post", "click");
}

// تتبع المشاهدات التلقائي
let observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            let itemId = entry.target.getAttribute("data-post-id");
            trackInteraction(itemId, "post", "view");
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.post').forEach(post => observer.observe(post));

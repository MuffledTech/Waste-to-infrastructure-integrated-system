/**
 * Waste to Infrastructure Management System — main.js v2.0
 * Handles: Bilingual toggle, AOS init, sidebar, animated counters, marketplace filter
 */

// ===== TRANSLATION OBJECT =====
const translations = {
    en: {
        nav_home: "Home", nav_login: "Login", nav_register: "Register",
        hero_badge: "Government of Nepal | Official System",
        hero_title: "Waste to Integrated Infrastructure Management System",
        hero_subtitle: "Join the national initiative to clean our cities and fund infrastructure projects through responsible waste management.",
        btn_citizen: "Register as Citizen", btn_login: "Login",
        stat_waste: "Total Waste Collected (kg)", stat_users: "Active Citizens", stat_infra: "Infrastructure Projects",
        step_1_title: "Report Waste", step_1_desc: "Snap a photo and pin the GPS location.",
        step_2_title: "Collection", step_2_desc: "Collectors verify and pick up the waste.",
        step_3_title: "Infrastructure", step_3_desc: "Funds generated build public projects.",
        footer_vision: "Building a Cleaner, Stronger Nation through responsible waste management.",
        footer_rights: "All Rights Reserved."
    },
    np: {
        nav_home: "गृहपृष्ठ", nav_login: "लगइन", nav_register: "दर्ता",
        hero_badge: "नेपाल सरकार | आधिकारिक प्रणाली",
        hero_title: "फोहोरबाट पूर्वाधार व्यवस्थापन प्रणाली",
        hero_subtitle: "हाम्रा सहरहरू सफा गर्न र जिम्मेवार फोहोर व्यवस्थापन मार्फत पूर्वाधार आयोजनाहरूलाई आर्थिक सहयोग गर्न राष्ट्रिय पहलमा सामेल हुनुहोस्।",
        btn_citizen: "नागरिकको रूपमा दर्ता गर्नुहोस्", btn_login: "लगइन",
        stat_waste: "जम्मा भएको फोहोर (के.जी.)", stat_users: "सक्रिय नागरिकहरू", stat_infra: "पूर्वाधार परियोजनाहरू",
        step_1_title: "फोहोर रिपोर्ट गर्नुहोस्", step_1_desc: "फोटो खिच्नुहोस् र GPS स्थान पिन गर्नुहोस्।",
        step_2_title: "संकलन", step_2_desc: "संकलकहरूले प्रमाणीकरण गरी फोहोर संकलन गर्छन्।",
        step_3_title: "पूर्वाधार", step_3_desc: "उत्पन्न रकम सार्वजनिक परियोजनाहरूमा प्रयोग हुन्छ।",
        footer_vision: "जिम्मेवार फोहोर व्यवस्थापन मार्फत स्वच्छ, समृद्ध राष्ट्र निर्माण।",
        footer_rights: "सर्वाधिकार सुरक्षित।"
    }
};

let currentLang = localStorage.getItem('site_lang') || 'en';

document.addEventListener('DOMContentLoaded', () => {
    // AOS
    if (typeof AOS !== 'undefined') AOS.init({ duration: 800, once: true, offset: 80 });

    // Apply saved language
    updateLanguage(currentLang);

    // Language buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            currentLang = btn.dataset.lang;
            localStorage.setItem('site_lang', currentLang);
            updateLanguage(currentLang);
        });
    });

    // Animated counters
    document.querySelectorAll('.counter').forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const step = Math.ceil(target / 150);
        const tick = () => {
            const current = +counter.innerText;
            if (current < target) { counter.innerText = Math.min(current + step, target); setTimeout(tick, 12); }
            else counter.innerText = target;
        };
        tick();
    });

    // Sticky navbar shadow on scroll (landing page)
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar-custom');
        if (nav) nav.style.boxShadow = window.scrollY > 50 ? '0 4px 20px rgba(0,0,0,0.15)' : '0 2px 8px rgba(0,0,0,0.08)';
    });
});

function updateLanguage(lang) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[lang]?.[key]) el.textContent = translations[lang][key];
    });
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });
}

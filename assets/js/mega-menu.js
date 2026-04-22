/**
 * Header Navigation System - Mobile & Desktop
 */

document.addEventListener('DOMContentLoaded', function() {
    initMobileMenuToggle();
    initMobileDropdowns();
    initOutsideClickClose();
});

/**
 * Initialize mobile menu toggle
 */
function initMobileMenuToggle() {
    const toggle = document.getElementById('headerToggle');
    const nav = document.getElementById('headerNav');
    
    if (toggle && nav) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            nav.classList.toggle('active');
            console.log('Menu toggled. Active:', nav.classList.contains('active'));
        });
    } else {
        console.error('Menu elements not found. Toggle:', !!toggle, 'Nav:', !!nav);
    }
}

/**
 * Initialize mobile dropdown toggles
 */
function initMobileDropdowns() {
    const navGroups = document.querySelectorAll('.nav-group');
    
    navGroups.forEach(group => {
        const link = group.querySelector(':scope > a');
        
        if (link) {
            link.addEventListener('click', function(e) {
                // Only toggle on mobile
                if (window.innerWidth < 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    group.classList.toggle('active');
                }
            });
        }
    });
}

/**
 * Close menu when clicking outside or on links
 */
function initOutsideClickClose() {
    const nav = document.getElementById('headerNav');
    const toggle = document.getElementById('headerToggle');
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (nav && toggle) {
            if (nav.classList.contains('active')) {
                const isNavClicked = nav.contains(e.target);
                const isToggleClicked = toggle.contains(e.target);
                
                if (!isNavClicked && !isToggleClicked) {
                    nav.classList.remove('active');
                    // Also close dropdowns
                    document.querySelectorAll('.nav-group.active').forEach(group => {
                        if (window.innerWidth < 768) {
                            group.classList.remove('active');
                        }
                    });
                }
            }
        }
    });
    
    // Close menu when clicking non-dropdown links
    document.querySelectorAll('.nav-items a').forEach(link => {
        link.addEventListener('click', function(e) {
            const parent = link.closest('.nav-group');
            
            // If it's not a dropdown parent link, close the menu
            if (!parent || (parent && !parent.querySelector('.dropdown, .mega-dropdown'))) {
                if (nav && window.innerWidth < 768) {
                    nav.classList.remove('active');
                }
            }
        });
    });
    
    // Close mobile menu on resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            if (nav) {
                nav.classList.remove('active');
            }
            document.querySelectorAll('.nav-group.active').forEach(group => {
                group.classList.remove('active');
            });
        }
    });
}

/**
 * Security & Comments Lite Tweaks - Browser Console Test Script
 * 
 * Instructions:
 * 1. Open your WordPress site in browser
 * 2. Open Developer Console (F12)
 * 3. Paste this entire script and press Enter
 * 4. Review the detailed test results
 * 
 * Note: Some tests require admin access or specific page contexts
 */

(function() {
    'use strict';
    
    // Test results storage
    const results = {
        passed: [],
        failed: [],
        warnings: [],
        info: []
    };
    
    // Styling for console output
    const styles = {
        title: 'font-size: 18px; font-weight: bold; color: #2271b1; padding: 10px 0;',
        pass: 'color: #00a32a; font-weight: bold;',
        fail: 'color: #d63638; font-weight: bold;',
        warn: 'color: #dba617; font-weight: bold;',
        info: 'color: #72aee6;',
        section: 'font-size: 14px; font-weight: bold; color: #1d2327; margin-top: 10px;'
    };
    
    console.log('%c🔒 Security & Comments Lite Tweaks - Plugin Test', styles.title);
    console.log('%cTesting plugin functionality...', styles.info);
    console.log('─────────────────────────────────────────────────────────');
    
    // =====================================================================
    // TEST 1: WordPress Version Hidden
    // =====================================================================
    console.log('%c\n🔒 TEST 1: WordPress Version Disclosure', styles.section);
    
    const generatorMeta = document.querySelector('meta[name="generator"]');
    if (!generatorMeta) {
        results.passed.push('✓ Generator meta tag completely removed');
        console.log('%c  ✓ Generator meta tag not found', styles.pass);
    } else if (generatorMeta.content.includes('WordPress')) {
        results.failed.push('✗ WordPress version exposed in generator meta tag');
        console.log('%c  ✗ Generator meta tag still present:', styles.fail);
        console.log('    - ' + generatorMeta.content);
    } else {
        results.info.push('ℹ Generator meta tag exists but doesn\'t mention WordPress');
        console.log('%c  ℹ Generator meta tag found (non-WordPress):', styles.info);
        console.log('    - ' + generatorMeta.content);
    }
    
    // Check for WordPress version in HTML comments or other locations
    const htmlSource = document.documentElement.outerHTML;
    const wpVersionPattern = /WordPress\s+[\d.]+/gi;
    const versionMatches = htmlSource.match(wpVersionPattern);
    
    if (!versionMatches) {
        results.passed.push('✓ No WordPress version found in HTML source');
        console.log('%c  ✓ No version strings found in page source', styles.pass);
    } else {
        results.warnings.push(`⚠ Found ${versionMatches.length} WordPress version reference(s) in HTML`);
        console.log('%c  ⚠ WordPress version references found:', styles.warn);
        versionMatches.forEach(match => console.log('    - ' + match));
    }
    
    // =====================================================================
    // TEST 2: Script/Style Version Parameters
    // =====================================================================
    console.log('%c\n🔢 TEST 2: Script/Style Version Parameters', styles.section);
    
    const scriptsWithVer = Array.from(document.scripts).filter(script => {
        return script.src && script.src.includes('?ver=');
    });
    
    const stylesWithVer = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).filter(link => {
        return link.href && link.href.includes('?ver=');
    });
    
    const totalWithVer = scriptsWithVer.length + stylesWithVer.length;
    
    if (totalWithVer === 0) {
        results.passed.push('✓ All version parameters removed from scripts/styles');
        console.log('%c  ✓ No version parameters found', styles.pass);
    } else {
        results.info.push(`ℹ Found ${scriptsWithVer.length} scripts and ${stylesWithVer.length} styles with version parameters`);
        console.log('%c  ℹ Version parameters found (expected if setting disabled):', styles.info);
        console.log(`    - ${scriptsWithVer.length} scripts with ?ver=`);
        console.log(`    - ${stylesWithVer.length} styles with ?ver=`);
        
        if (scriptsWithVer.length > 0 && scriptsWithVer.length <= 5) {
            console.log('  Example scripts:');
            scriptsWithVer.slice(0, 5).forEach(s => console.log('    - ' + s.src));
        }
    }
    
    // =====================================================================
    // TEST 3: Comment Scripts Optimization
    // =====================================================================
    console.log('%c\n💬 TEST 3: Comment Reply Script', styles.section);
    
    const commentReplyScript = Array.from(document.scripts).find(script => {
        return script.src && script.src.includes('comment-reply');
    });
    
    const isCommentPage = document.querySelector('.comments-area, #comments, #respond');
    const commentsOpen = document.querySelector('.comments-area:not(.comments-closed)');
    
    if (!commentReplyScript) {
        if (!isCommentPage) {
            results.passed.push('✓ Comment reply script not loaded (not a comment page)');
            console.log('%c  ✓ Comment reply script correctly not loaded (not on comment page)', styles.pass);
        } else if (!commentsOpen) {
            results.passed.push('✓ Comment reply script not loaded (comments closed)');
            console.log('%c  ✓ Comment reply script correctly not loaded (comments closed)', styles.pass);
        } else {
            results.warnings.push('⚠ Comment reply script not loaded but comments appear open');
            console.log('%c  ⚠ No comment reply script but comments seem enabled', styles.warn);
        }
    } else {
        if (isCommentPage && commentsOpen) {
            results.info.push('ℹ Comment reply script loaded (comments are open)');
            console.log('%c  ℹ Comment reply script loaded - this is normal if comments are open', styles.info);
        } else {
            results.failed.push('✗ Comment reply script loaded unnecessarily');
            console.log('%c  ✗ Comment reply script loaded but not needed', styles.fail);
            console.log('    - ' + commentReplyScript.src);
        }
    }
    
    // =====================================================================
    // TEST 4: Comment Hyperlinks
    // =====================================================================
    console.log('%c\n🔗 TEST 4: Comment Hyperlinks', styles.section);
    
    const commentLinks = document.querySelectorAll('.comment-content a, .comment-text a');
    
    if (commentLinks.length === 0) {
        results.info.push('ℹ No comments with links found (cannot test)');
        console.log('%c  ℹ No comment links found to test', styles.info);
        console.log('    To test: Post a comment with a URL and check if it becomes clickable');
    } else {
        results.info.push(`ℹ Found ${commentLinks.length} link(s) in comments`);
        console.log('%c  ℹ Found comment links - manual verification needed:', styles.info);
        console.log(`    - ${commentLinks.length} links found in comments`);
        console.log('    - If "Disable Comment Hyperlinks" is ON, URLs should appear as plain text');
        console.log('    - If links are clickable, the feature may be disabled or overridden by theme');
    }
    
    // =====================================================================
    // TEST 5: Comments Disabled Site-wide
    // =====================================================================
    console.log('%c\n🚫 TEST 5: Comments Disabled Site-wide', styles.section);
    
    const commentForm = document.querySelector('#respond, .comment-respond');
    const commentsList = document.querySelector('#comments, .comments-area');
    const existingComments = document.querySelectorAll('.comment, .comment-list li');
    
    if (!commentForm && !commentsList) {
        results.passed.push('✓ Comment form and comment area not found (likely disabled)');
        console.log('%c  ✓ No comment forms or comment areas detected', styles.pass);
    } else {
        results.info.push('ℹ Comment elements found on page');
        console.log('%c  ℹ Comment elements detected:', styles.info);
        if (commentForm) console.log('    - Comment form present');
        if (commentsList) console.log('    - Comment area present');
        if (existingComments.length > 0) console.log(`    - ${existingComments.length} comment(s) visible`);
        console.log('    If "Disable Comments Site-wide" is ON, these should not appear');
    }
    
    // =====================================================================
    // TEST 6: Trackbacks & Pingbacks Headers
    // =====================================================================
    console.log('%c\n📡 TEST 6: Pingback Header', styles.section);
    
    console.log('%c  ℹ Checking for X-Pingback header...', styles.info);
    console.log('    (Results will show after fetch completes)');
    
    fetch(window.location.href, { 
        method: 'HEAD',
        cache: 'no-cache'
    }).then(response => {
        const pingbackHeader = response.headers.get('X-Pingback');
        
        if (!pingbackHeader) {
            results.passed.push('✓ X-Pingback header removed');
            console.log('%c  ✓ No X-Pingback header found', styles.pass);
        } else {
            results.failed.push('✗ X-Pingback header still present');
            console.log('%c  ✗ X-Pingback header found:', styles.fail);
            console.log('    - ' + pingbackHeader);
        }
    }).catch(error => {
        results.warnings.push('⚠ Could not check X-Pingback header');
        console.log('%c  ⚠ Failed to check headers:', styles.warn);
        console.log('    - ' + error.message);
    });
    
    // =====================================================================
    // TEST 7: Additional Security Headers
    // =====================================================================
    console.log('%c\n🛡️ TEST 7: Additional Discovery Tags', styles.section);
    
    const rsdLink = document.querySelector('link[rel="EditURI"]');
    const wlwLink = document.querySelector('link[rel="wlwmanifest"]');
    const shortlink = document.querySelector('link[rel="shortlink"]');
    
    if (!rsdLink && !wlwLink && !shortlink) {
        results.info.push('ℹ No unnecessary discovery tags found');
        console.log('%c  ✓ Clean HTML head - no RSD, WLW, or shortlink tags', styles.pass);
    } else {
        console.log('%c  ℹ Discovery tags found (these are not directly controlled by this plugin):', styles.info);
        if (rsdLink) console.log('    - RSD (EditURI): ' + rsdLink.href);
        if (wlwLink) console.log('    - Windows Live Writer: ' + wlwLink.href);
        if (shortlink) console.log('    - Shortlink: ' + shortlink.href);
    }
    
    // =====================================================================
    // TEST 8: Backend Features (Info Only)
    // =====================================================================
    console.log('%c\n⚙️ TEST 8: Backend-Only Features', styles.section);
    console.log('%c  ℹ The following features require admin access to test:', styles.info);
    console.log('    1. Application Passwords - Check wp-admin → Users → Profile');
    console.log('    2. Code Editors - Check wp-admin → Appearance → Theme/Plugin Editor');
    console.log('    3. Admin Email Confirmation - Check for popup prompt in admin');
    console.log('    These cannot be tested from the frontend.');
    
    // =====================================================================
    // FINAL SUMMARY
    // =====================================================================
    console.log('%c\n═══════════════════════════════════════════════════════', styles.section);
    console.log('%c📊 TEST SUMMARY', styles.title);
    console.log('%c═══════════════════════════════════════════════════════', styles.section);
    
    console.log(`%c\n✓ PASSED: ${results.passed.length}`, styles.pass);
    results.passed.forEach(msg => console.log(`  ${msg}`));
    
    if (results.failed.length > 0) {
        console.log(`%c\n✗ FAILED: ${results.failed.length}`, styles.fail);
        results.failed.forEach(msg => console.log(`  ${msg}`));
    }
    
    if (results.warnings.length > 0) {
        console.log(`%c\n⚠ WARNINGS: ${results.warnings.length}`, styles.warn);
        results.warnings.forEach(msg => console.log(`  ${msg}`));
    }
    
    if (results.info.length > 0) {
        console.log(`%c\nℹ INFO: ${results.info.length}`, styles.info);
        results.info.forEach(msg => console.log(`  ${msg}`));
    }
    
    // =====================================================================
    // MANUAL TESTING INSTRUCTIONS
    // =====================================================================
    console.log('%c\n═══════════════════════════════════════════════════════', styles.section);
    console.log('%c🔍 MANUAL TESTS REQUIRED', styles.title);
    console.log('%c═══════════════════════════════════════════════════════', styles.section);
    
    console.log(`%c
The following features require manual testing in WordPress admin:

1. HIDE WORDPRESS VERSION
   ✓ Automated test completed above
   → Check: No version info in page source

2. DISABLE GENERATOR META TAG  
   ✓ Automated test completed above
   → Check: No <meta name="generator"> tag

3. REMOVE SCRIPT/STYLE VERSIONS
   ✓ Automated test completed above
   → Check: No ?ver= in asset URLs

4. DISABLE APPLICATION PASSWORDS
   → Go to: wp-admin → Users → Your Profile
   → Check: "Application Passwords" section should NOT appear

5. DISABLE CODE EDITORS
   → Go to: wp-admin → Appearance → Theme/Plugin Editor
   → Check: Should redirect or show "disabled" message

6. DISABLE ADMIN EMAIL CONFIRMATION
   → Check: No admin email verification popup appears
   → Usually shows every 6 months if enabled

7. OPTIMIZE COMMENT SCRIPTS
   ✓ Automated test completed above
   → Check: comment-reply.js only loads on posts with open comments

8. DISABLE COMMENT HYPERLINKS
   → Post a test comment with a URL like: https://example.com
   → Check: URL should appear as plain text, not clickable link

9. DISABLE TRACKBACKS & PINGBACKS
   ✓ Automated test completed above (X-Pingback header)
   → Edit a post → Discussion settings
   → Check: Pingback options should be disabled

10. DISABLE COMMENTS SITE-WIDE
    ✓ Automated test completed above
    → Check: No comment forms anywhere
    → Check: wp-admin → Comments menu should be hidden/redirect
    → Check: No comment meta boxes on post edit screens
`, styles.info);
    
    console.log('%c\n═══════════════════════════════════════════════════════', styles.section);
    console.log('%c✅ TESTING COMPLETE', styles.title);
    console.log('%c═══════════════════════════════════════════════════════\n', styles.section);
    
    console.log('%c💡 Note:', styles.info);
    console.log('  - PASS results mean the feature is working as expected (when enabled)');
    console.log('  - FAIL results mean the feature is NOT working (if it should be enabled)');
    console.log('  - INFO results need manual verification or context-specific checks');
    console.log('  - Go to: wp-admin → Tools → Security & Comments to verify your settings\n');
    
    // Return summary object for programmatic access
    return {
        passed: results.passed.length,
        failed: results.failed.length,
        warnings: results.warnings.length,
        info: results.info.length,
        details: results
    };
})();

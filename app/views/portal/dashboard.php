<?php
/**
 * Attorney Lead Dashboard — auth-gated, noindex, FULLY DYNAMIC.
 *
 * All data flows from the server:
 *   - Attorney identity from session (loadAttorneyForUser)
 *   - Wallet balance / plan from `attorneys` table
 *   - Leads + bids from `marketplace_leads` / `marketplace_bids`
 *   - Bids placed via AJAX → POST /for-attorneys/dashboard/place-bid
 *   - Live updates via 15-second polling of /for-attorneys/dashboard/state
 *
 * Design (CSS + DOM structure) kept verbatim from the original mockup.
 *
 * @var array        $state     ['me' => {...}, 'leads' => [...]] from controller
 * @var array|null   $attorney  Logged-in attorney row
 * @var string       $csrf      CSRF token for AJAX bid POSTs + logout
 */

$me      = $state['me'];
$leads   = $state['leads'];
$missing = empty($attorney);
?><!DOCTYPE html>
<html lang="en" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>LawMillion — Attorney Lead Dashboard</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#F5F3EE;--surface:#FFFFFF;--surface2:#F0EDE6;--surface3:#E5E1D8;
  --border:#DDD9D0;--border2:#C5C0B5;
  --text:#1C1A17;--text2:#4A4640;--text3:#7A756E;--text4:#A8A39C;
  --gold:#B8861E;--gold-bg:#FBF4E2;--gold-border:#DFC070;--gold-text:#7A5800;
  --green:#16703E;--green-bg:#E8F5EE;--green-border:#70C498;--green-dark:#0E5030;
  --red:#A01E2A;--red-bg:#FBEAEC;--red-border:#D88090;
  --blue:#1A4480;--blue-bg:#EAF0FB;--blue-border:#88A8DC;
  --navy:#0A0F1E;--purple:#5A2DA0;--purple-bg:#F2EDFB;
  --r:10px;--rs:7px;--sh:0 2px 10px rgba(0,0,0,.07);--shm:0 6px 28px rgba(0,0,0,.13);
}
.dark{
  --bg:#141210;--surface:#1E1C18;--surface2:#272420;--surface3:#302D28;
  --border:#3A3630;--border2:#504A42;
  --text:#F0EDE6;--text2:#C8C2B8;--text3:#8A8478;--text4:#5A5650;
  --gold-bg:#2A2210;--gold-border:#6A4E10;
  --green-bg:#0C1E14;--green-border:#1A5030;
  --red-bg:#1E0C0E;--red-border:#6A2830;
  --blue-bg:#0E1628;--blue-border:#1E3860;
  --purple-bg:#1A1028;
}
html,body{height:100%;overflow:hidden}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;font-size:14px;background:var(--bg);color:var(--text);line-height:1.5;transition:background .25s,color .25s}
button,input{font-family:inherit;cursor:pointer}
input[type=number]::-webkit-inner-spin-button{opacity:1;height:28px}

/* ──── LAYOUT ──── */
.dash{display:flex;height:100vh;overflow:hidden}

/* ──── SIDEBAR ──── */
.sb{width:220px;background:var(--navy);display:flex;flex-direction:column;flex-shrink:0;transition:width .25s}
.dark .sb{background:#08080E}
.sb-logo{padding:16px 16px 12px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:10px}
.sb-logo-mark{width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#C8992B,#8B1A2B);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#FFFFFF;flex-shrink:0}
.sb-logo-text{font-size:14px;font-weight:700;color:#FFFFFF;letter-spacing:-.01em}
.sb-logo-sub{font-size:10px;color:rgba(255,255,255,.38)}
.ns{padding:12px 14px 4px;font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.28)}
.nb{display:flex;align-items:center;gap:9px;padding:8px 14px;font-size:12.5px;color:rgba(255,255,255,.6);background:none;border:none;width:100%;text-align:left;border-radius:0;transition:all .15s;position:relative}
.nb:hover{background:rgba(255,255,255,.06);color:#FFFFFF}
.nb.act{background:rgba(200,153,43,.16);color:#E8B845}
.nb .ni{font-size:13px;width:16px;text-align:center;flex-shrink:0}
.nb .nl{flex:1;white-space:nowrap;overflow:hidden}
.nbadge{font-size:10px;padding:1px 7px;border-radius:20px;font-weight:700;flex-shrink:0}
.nb-gold{background:rgba(200,153,43,.22);color:#E8B845}
.nb-green{background:rgba(22,112,62,.28);color:#70C498}
.nb-red{background:rgba(160,30,42,.3);color:#F08090;animation:pulse-badge 1.5s infinite}
@keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:.5}}
.sb-bottom{padding:12px 14px;border-top:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:9px;margin-top:auto}
.sb-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#C8992B,#8B1A2B);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#FFFFFF;flex-shrink:0}
.sb-atty-name{font-size:12px;font-weight:600;color:#FFFFFF}
.sb-atty-plan{font-size:10px;color:var(--gold-border)}

/* ──── MAIN ──── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}

/* ──── TOPBAR ──── */
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 18px;height:52px;display:flex;align-items:center;gap:12px;flex-shrink:0}
.tb-title{font-size:15px;font-weight:700;flex-shrink:0}
.tb-search{flex:1;max-width:240px;padding:7px 12px;border:1px solid var(--border);border-radius:var(--rs);font-size:13px;background:var(--surface2);color:var(--text);outline:none;transition:all .2s}
.tb-search:focus{border-color:var(--gold);background:var(--surface)}
.tb-right{display:flex;align-items:center;gap:9px;margin-left:auto}
.wallet-pill{display:flex;align-items:center;gap:6px;padding:6px 13px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:var(--rs);font-size:13px;font-weight:700;color:var(--green);cursor:pointer;transition:all .2s}
.wallet-pill:hover{background:var(--green-bg);filter:brightness(0.95)}
.tb-icon-btn{width:34px;height:34px;border-radius:var(--rs);background:var(--surface2);border:1px solid var(--border);font-size:15px;color:var(--text3);display:flex;align-items:center;justify-content:center;transition:all .2s}
.tb-icon-btn:hover{border-color:var(--gold);color:var(--gold)}

/* ──── LIVE TICKER ──── */
.ticker{background:var(--navy);height:30px;display:flex;align-items:center;overflow:hidden;flex-shrink:0;position:relative}
.dark .ticker{background:#08080E}
.ticker-label{padding:0 12px;font-size:10px;font-weight:700;color:rgba(255,255,255,.4);letter-spacing:.08em;text-transform:uppercase;white-space:nowrap;flex-shrink:0;border-right:1px solid rgba(255,255,255,.08)}
.ticker-track{display:flex;align-items:center;gap:0;overflow:hidden;flex:1}
.ticker-inner{display:flex;align-items:center;gap:0;white-space:nowrap;animation:ticker-scroll 40s linear infinite}
.ticker-inner:hover{animation-play-state:paused}
@keyframes ticker-scroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.ticker-item{padding:0 18px;font-size:11px;color:rgba(255,255,255,.6);border-right:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:6px}
.ticker-item.mine{color:#E8B845}
.ticker-item.outbid{color:#F08090}
.ticker-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}

/* ──── CONTENT ──── */
.content{flex:1;overflow-y:auto;padding:18px}
.content::-webkit-scrollbar{width:5px}
.content::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* ──── STATS ──── */
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:16px}
.sc{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 15px;position:relative;overflow:hidden;cursor:pointer;transition:all .2s}
.sc:hover{border-color:var(--gold-border);box-shadow:var(--sh)}
.sc-accent{position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--r) var(--r) 0 0}
.sc-label{font-size:10.5px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
.sc-val{font-size:24px;font-weight:800;line-height:1}
.sc-sub{font-size:11px;color:var(--text3);margin-top:3px}
.sc-icon{position:absolute;right:13px;top:12px;font-size:20px;opacity:.2}

/* ──── FILTERS ──── */
.filter-bar{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:10px 14px;margin-bottom:14px;display:flex;flex-direction:column;gap:9px}
.filter-row{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.fl{font-size:11.5px;color:var(--text3);font-weight:700;flex-shrink:0;min-width:46px}
.chip{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text3);transition:all .15s;flex-shrink:0}
.chip:hover{border-color:var(--gold);color:var(--gold-text)}
.chip.ac{background:var(--gold-bg);border-color:var(--gold-border);color:var(--gold-text)}
.chip.uh.ac{background:var(--red-bg);border-color:var(--red-border);color:var(--red)}
.chip.um.ac{background:var(--gold-bg);border-color:var(--gold-border);color:var(--gold-text)}
.chip.ul.ac{background:var(--green-bg);border-color:var(--green-border);color:var(--green)}
.sb-btn{padding:5px 12px;border-radius:var(--rs);font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text3);transition:all .15s}
.sb-btn:hover,.sb-btn.ac{background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue)}

/* ──── OUTBID ALERT BAR ──── */
.outbid-bar{display:flex;align-items:center;justify-content:space-between;padding:11px 15px;background:var(--red-bg);border:1px solid var(--red-border);border-radius:var(--r);margin-bottom:14px;gap:12px}
.ob-text{font-size:13px;color:var(--red);font-weight:500}
.ob-btn{padding:5px 13px;border-radius:var(--rs);border:1px solid var(--red-border);background:var(--red);color:#FFFFFF;font-size:12px;font-weight:700;white-space:nowrap}
.ob-btn:hover{opacity:.88}

/* ──── PROFILE INCOMPLETE BANNER ──── */
.profile-banner{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:var(--gold-bg);border:1px solid var(--gold-border);border-radius:var(--r);margin-bottom:14px;gap:12px}
.profile-banner-text{font-size:13.5px;color:var(--gold-text);font-weight:600}
.profile-banner a{color:var(--gold-text);text-decoration:underline;font-weight:700}

/* ──── LEADS GRID ──── */
.leads-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(390px,1fr));gap:15px}
.empty-state{grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text3)}
.empty-icon{font-size:40px;margin-bottom:14px;opacity:.4}
.empty-text{font-size:14px;margin-bottom:16px}

/* ──── LEAD CARD ──── */
.lc{background:var(--surface);border:2px solid var(--border);border-radius:14px;display:flex;flex-direction:column;overflow:hidden;transition:border-color .2s,box-shadow .2s}
.lc:hover{box-shadow:var(--sh)}
.lc.lc-leading{border-color:var(--green-border)}
.lc.lc-outbid{border-color:var(--red-border)}
.lc.lc-won{border-color:var(--green-border);box-shadow:0 0 0 3px rgba(22,112,62,.12)}
.lc.lc-closed{opacity:.68;border-color:var(--border)}

/* CARD TOP BAND */
.lc-band{height:4px;width:100%}

/* CARD HEAD */
.lc-head{padding:13px 14px 10px}
.lc-head-row1{display:flex;align-items:flex-start;gap:8px;margin-bottom:7px}
.lc-title{font-size:13.5px;font-weight:700;color:var(--text);flex:1;line-height:1.35}
.lc-tags{display:flex;gap:5px;flex-wrap:wrap}

/* TAGS */
.tag{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;white-space:nowrap}
.tag-h{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.tag-m{background:var(--gold-bg);color:var(--gold-text);border:1px solid var(--gold-border)}
.tag-l{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.tag-area{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-border)}
.tag-sub{background:var(--surface2);color:var(--text3);border:1px solid var(--border)}
.tag-won{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.tag-cl{background:var(--surface2);color:var(--text3);border:1px solid var(--border)}
.tag-val{background:var(--purple-bg);color:var(--purple);border:1px solid rgba(90,45,160,.2)}

.lc-meta{font-size:11.5px;color:var(--text3);display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}

/* CARD DESC */
.lc-desc{padding:9px 14px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text2);line-height:1.65}
.lc-desc-txt{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:6px}
.lc-facts{display:flex;gap:5px;flex-wrap:wrap}
.fc{font-size:11px;padding:2px 9px;border-radius:20px;border:1px solid var(--border);color:var(--text3);background:var(--surface2);white-space:nowrap}
.fc::before{content:"✓ ";color:var(--green);font-weight:700}

/* OUTBID CARD BANNER */
.lc-outbid-banner{margin:0 14px 10px;padding:8px 12px;background:var(--red-bg);border:1px solid var(--red-border);border-radius:var(--rs);display:flex;align-items:center;justify-content:space-between;gap:8px}
.lob-text{font-size:12px;color:var(--red);font-weight:600}
.lob-raise{padding:4px 12px;border-radius:20px;background:var(--red);color:#FFFFFF;font-size:11.5px;font-weight:700;border:none;white-space:nowrap;transition:all .2s}
.lob-raise:hover{opacity:.85}

/* LEADING CARD BANNER */
.lc-leading-banner{margin:0 14px 10px;padding:8px 12px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:var(--rs);display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12px;color:var(--green);font-weight:600}

/* BID BAR */
.lc-bid-bar{padding:9px 14px;border-top:1px solid var(--border);display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.bb-item{}
.bb-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--text4);font-weight:700;margin-bottom:3px}
.bb-val{font-size:17px;font-weight:800;line-height:1}
.bb-sub{font-size:11px;color:var(--text3);margin-top:2px}
.c-gold{color:var(--gold)}
.c-green{color:var(--green)}
.c-red{color:var(--red)}
.c-muted{color:var(--text4)}

/* TIMER RING */
.timer-wrap{display:flex;align-items:center;gap:6px}
.timer-ring{position:relative;width:36px;height:36px;flex-shrink:0}
.tr-svg{transform:rotate(-90deg)}
.tr-bg{fill:none;stroke:var(--border);stroke-width:3}
.tr-fill{fill:none;stroke-width:3;stroke-linecap:round;transition:stroke-dashoffset .9s linear,stroke .5s}
.timer-val{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:9.5px;font-weight:800;line-height:1}
.timer-wrap.hot .tr-fill,.timer-wrap.hot .timer-val{stroke:var(--red);color:var(--red)}
.timer-wrap.hot .timer-ring{animation:ring-pulse 1s infinite}
@keyframes ring-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}
.timer-wrap.warm .tr-fill,.timer-wrap.warm .timer-val{stroke:var(--gold);color:var(--gold)}
.timer-wrap.cool .tr-fill,.timer-wrap.cool .timer-val{stroke:var(--green);color:var(--green)}
.timer-wrap.closed .tr-fill,.timer-wrap.closed .timer-val{stroke:var(--text4);color:var(--text4)}
.timer-label{font-size:11px;color:var(--text3)}

/* BIDDERS AVATARS STACK */
.bidder-stack{display:flex;align-items:center}
.bs-av{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;border:2px solid var(--surface);margin-left:-6px;flex-shrink:0}
.bs-av:first-child{margin-left:0}
.bs-av.mine-av{background:linear-gradient(135deg,var(--blue),#3060C0);color:#FFFFFF}
.bs-av.other-av{background:var(--surface2);color:var(--text3);border-color:var(--surface)}
.bs-more{width:22px;height:22px;border-radius:50%;background:var(--surface2);color:var(--text3);font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-left:-6px;border:2px solid var(--surface)}

/* CONTACT STRIP */
.lc-contact{padding:8px 14px;border-top:1px solid var(--border);display:flex;gap:14px;font-size:12px;align-items:center;background:var(--surface2)}
.lc-contact.uc{background:var(--green-bg)}
.ci{display:flex;align-items:center;gap:5px}
.ci-icon{opacity:.6;font-size:13px}
.ci-val{font-weight:700;color:var(--green)}
.ci-mask{font-family:monospace;letter-spacing:.14em;color:var(--text4);font-size:11px}
.win-note{margin-left:auto;font-size:11px;color:var(--text4);font-style:italic}

/* CARD ACTIONS */
.lc-actions{padding:11px 14px;border-top:1px solid var(--border);display:flex;gap:7px;align-items:center}
.bid-wrap{display:flex;flex:1;align-items:center;gap:5px}
.bid-quick-row{display:flex;gap:4px}
.bq{padding:6px 9px;border-radius:var(--rs);font-size:12px;font-weight:700;border:1.5px solid var(--border);background:var(--surface2);color:var(--text2);transition:all .15s;white-space:nowrap}
.bq:hover{border-color:var(--gold-border);color:var(--gold-text);background:var(--gold-bg)}
.bq.bq-ac{border-color:var(--gold-border);background:var(--gold-bg);color:var(--gold-text)}
.bid-input-group{display:flex;align-items:center;gap:4px;flex:1;max-width:150px}
.bid-dollar{font-size:14px;font-weight:800;color:var(--text3)}
.bid-inp{width:70px;padding:7px 8px;border:1.5px solid var(--border);border-radius:var(--rs);font-size:14px;font-weight:800;background:var(--surface2);color:var(--text);outline:none;text-align:center;transition:all .2s}
.bid-inp:focus{border-color:var(--gold);background:var(--surface)}
.btn-bid{padding:8px 16px;border-radius:var(--rs);background:var(--gold);color:var(--navy);font-size:13px;font-weight:800;border:none;transition:all .2s;white-space:nowrap}
.btn-bid:hover{background:#D4A030;transform:translateY(-1px)}
.btn-bid:active{transform:translateY(0)}
.btn-bid:disabled{opacity:.6;cursor:not-allowed;transform:none}
.btn-contact-card{flex:1;padding:9px;border-radius:var(--rs);background:var(--green);color:#FFFFFF;font-size:13px;font-weight:700;border:none;text-align:center;transition:all .2s}
.btn-contact-card:hover{background:var(--green-dark);transform:translateY(-1px)}
.btn-details{padding:8px 13px;border-radius:var(--rs);background:var(--surface2);border:1px solid var(--border);color:var(--text2);font-size:12.5px;font-weight:600;transition:all .15s;white-space:nowrap}
.btn-details:hover{border-color:var(--gold-border);color:var(--gold-text)}
.btn-first-bid{flex:1;padding:9px;border-radius:var(--rs);background:linear-gradient(135deg,var(--gold),#D4A030);color:var(--navy);font-size:13px;font-weight:800;border:none;text-align:center;transition:all .2s}
.btn-first-bid:hover{opacity:.9;transform:translateY(-1px)}
</style>
<style>
/* ──── SLIDE PANEL ──── */
.panel-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;display:flex;justify-content:flex-end;opacity:0;pointer-events:none;transition:opacity .25s}
.panel-overlay.open{opacity:1;pointer-events:all}
.slide-panel{width:500px;max-width:100vw;height:100vh;background:var(--surface);display:flex;flex-direction:column;overflow:hidden;transform:translateX(100%);transition:transform .28s cubic-bezier(.4,0,.2,1)}
.panel-overlay.open .slide-panel{transform:translateX(0)}
.ph{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:10px;background:var(--surface)}
.ph-left{display:flex;flex-direction:column;gap:2px}
.ph-title{font-size:14.5px;font-weight:700}
.ph-sub{font-size:11.5px;color:var(--text3)}
.ph-right{display:flex;align-items:center;gap:9px}
.ph-timer{font-size:13px;font-weight:700}
.ph-close{width:30px;height:30px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);font-size:14px;color:var(--text3);display:flex;align-items:center;justify-content:center}
.ph-close:hover{border-color:var(--red-border);color:var(--red)}
.pb{flex:1;overflow-y:auto;padding:18px}
.pb::-webkit-scrollbar{width:5px}
.pb::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
.ps{margin-bottom:18px}
.ps-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.ps-title{font-size:10px;text-transform:uppercase;letter-spacing:.1em;font-weight:700;color:var(--text4)}
.ps-badge{font-size:10.5px;padding:2px 9px;border-radius:20px;font-weight:700}
.panel-title-text{font-size:16px;font-weight:800;line-height:1.3;margin-bottom:8px}
.panel-tags{display:flex;gap:6px;flex-wrap:wrap}
.p-desc{background:var(--surface2);border-radius:var(--rs);padding:12px 13px;font-size:13px;color:var(--text2);line-height:1.7}
.p-facts{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.p-fact{background:var(--surface2);border-radius:var(--rs);padding:9px 11px;font-size:12px;color:var(--text2);display:flex;gap:7px;align-items:flex-start;line-height:1.45}
.pf-ck{color:var(--green);font-weight:800;flex-shrink:0}
.p-info-table{border:1px solid var(--border);border-radius:var(--rs);overflow:hidden}
.p-info-row{display:flex;justify-content:space-between;align-items:center;padding:9px 13px;border-bottom:1px solid var(--border);font-size:13px}
.p-info-row:last-child{border:none}
.p-info-row:nth-child(even){background:var(--surface2)}
.p-info-k{color:var(--text3);font-weight:500}
.p-info-v{font-weight:700;text-align:right;max-width:60%}
.p-info-v.green{color:var(--green)}
.p-info-v.gold{color:var(--gold)}
.p-mask{font-family:monospace;letter-spacing:.14em;color:var(--text4);font-size:12px;background:var(--surface2);padding:1px 9px;border-radius:4px}
.p-contact-unlocked{background:var(--green-bg);border-radius:var(--rs);overflow:hidden;border:1px solid var(--green-border)}
.p-contact-unlocked .p-info-row{background:var(--green-bg)}
.p-contact-unlocked .p-info-row:nth-child(even){background:rgba(22,112,62,.06)}
.pca{display:flex;gap:9px;margin-top:10px}
.btn-call-p{flex:1;padding:11px;border-radius:var(--rs);background:var(--green);color:#FFFFFF;font-size:13.5px;font-weight:700;border:none;text-align:center;transition:all .2s;text-decoration:none}
.btn-call-p:hover{background:var(--green-dark);transform:translateY(-1px)}
.btn-email-p{flex:1;padding:11px;border-radius:var(--rs);background:var(--surface2);border:1.5px solid var(--green-border);color:var(--green);font-size:13.5px;font-weight:700;text-align:center;transition:all .2s;text-decoration:none}
.btn-email-p:hover{background:var(--green-bg)}

/* BID SECTION in panel */
.bid-panel-box{background:var(--surface2);border:1.5px solid var(--border);border-radius:var(--r);padding:15px;margin-bottom:14px}
.bpb-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-size:12.5px;color:var(--text3)}
.bpb-header strong{color:var(--text)}
.bpb-row{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.bpb-dollar{font-size:22px;font-weight:800;color:var(--text3)}
.bpb-input{flex:1;padding:11px 13px;border:2px solid var(--border);border-radius:var(--rs);font-size:20px;font-weight:800;background:var(--surface);color:var(--text);outline:none;transition:all .2s;text-align:center}
.bpb-input:focus{border-color:var(--gold)}
.bpb-input::-webkit-inner-spin-button{opacity:1}
.bpb-btn{padding:11px 20px;border-radius:var(--rs);background:var(--gold);color:var(--navy);font-size:14px;font-weight:800;border:none;white-space:nowrap;transition:all .2s}
.bpb-btn:hover{background:#D4A030;transform:translateY(-1px)}
.bpb-btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
.bpb-quick{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:11px}
.bpb-q{padding:5px 13px;border-radius:20px;font-size:12.5px;font-weight:700;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);transition:all .15s}
.bpb-q:hover,.bpb-q.ac{background:var(--gold-bg);border-color:var(--gold-border);color:var(--gold-text)}
.bpb-note{font-size:11.5px;color:var(--text4);line-height:1.65;padding:10px 12px;background:var(--surface);border-radius:var(--rs);border:1px solid var(--border)}
.bpb-note strong{color:var(--text2)}

/* BID BOARD */
.bid-board{display:flex;flex-direction:column;gap:7px}
.bid-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--rs);border:1.5px solid var(--border);transition:all .15s}
.bid-row.br-top{border-color:var(--gold-border);background:var(--gold-bg)}
.bid-row.br-mine{border-color:var(--blue-border);background:var(--blue-bg)}
.bid-row.br-top.br-mine{background:linear-gradient(135deg,var(--gold-bg),var(--blue-bg));border-color:var(--gold-border)}
.bid-av{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
.bav-mine{background:linear-gradient(135deg,var(--blue),#3060C0);color:#FFFFFF}
.bav-other{background:var(--surface2);color:var(--text3);border:1.5px solid var(--border)}
.bid-info{flex:1;min-width:0}
.bid-name{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bid-time{font-size:11px;color:var(--text3)}
.bid-amt{font-size:18px;font-weight:800}
.bid-rank{font-size:10.5px;padding:2px 8px;border-radius:20px;font-weight:700;margin-left:6px}
.br1{background:var(--gold-bg);color:var(--gold-text);border:1px solid var(--gold-border)}
.br2{background:var(--surface2);color:var(--text3)}
.no-bids-msg{font-size:13px;color:var(--text3);padding:12px;background:var(--surface2);border-radius:var(--rs);text-align:center;border:1px dashed var(--border)}

/* STATUS BANNERS */
.sb-banner{border-radius:var(--rs);padding:12px 14px;margin-bottom:14px;font-size:13px;line-height:1.6;display:flex;gap:10px;align-items:flex-start}
.sb-leading{background:var(--green-bg);border:1px solid var(--green-border);color:var(--green-dark)}
.sb-outbid{background:var(--red-bg);border:1px solid var(--red-border);color:var(--red)}
.sb-won{background:var(--green-bg);border:1px solid var(--green-border);color:var(--green-dark)}
.sb-icon{font-size:17px;flex-shrink:0;margin-top:1px}

/* ──── CONFIRM MODAL ──── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:var(--surface);border-radius:16px;padding:28px;max-width:380px;width:100%;box-shadow:var(--shm);transform:scale(.94);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1)}
.modal-icon{font-size:36px;text-align:center;margin-bottom:14px}
.modal-title{font-size:18px;font-weight:800;text-align:center;margin-bottom:8px}
.modal-sub{font-size:13.5px;color:var(--text2);text-align:center;line-height:1.65;margin-bottom:20px}
.modal-detail{background:var(--surface2);border-radius:var(--rs);padding:14px;margin-bottom:20px;border:1px solid var(--border)}
.modal-detail-row{display:flex;justify-content:space-between;font-size:13px;padding:4px 0}
.modal-detail-row .mk{color:var(--text3)}
.modal-detail-row .mv{font-weight:700}
.modal-detail-row .mv.gold{color:var(--gold)}
.modal-btns{display:flex;gap:10px}
.modal-cancel{flex:1;padding:11px;border-radius:var(--rs);background:var(--surface2);border:1px solid var(--border);color:var(--text2);font-size:14px;font-weight:600}
.modal-cancel:hover{border-color:var(--red-border);color:var(--red)}
.modal-confirm{flex:1;padding:11px;border-radius:var(--rs);background:var(--gold);border:none;color:var(--navy);font-size:14px;font-weight:800;transition:all .2s}
.modal-confirm:hover{background:#D4A030;transform:translateY(-1px)}
.modal-confirm:disabled{opacity:.6;cursor:not-allowed;transform:none}

/* ──── WON LEADS TABLE ──── */
.won-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden}
.wt-head{display:grid;grid-template-columns:1.8fr 1.1fr 0.8fr 1fr 1.3fr 1.4fr 0.8fr;padding:10px 15px;background:var(--surface2);font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--border)}
.wt-row{display:grid;grid-template-columns:1.8fr 1.1fr 0.8fr 1fr 1.3fr 1.4fr 0.8fr;padding:12px 15px;border-bottom:1px solid var(--border);align-items:center;transition:background .15s;cursor:pointer}
.wt-row:last-child{border:none}
.wt-row:hover{background:var(--green-bg)}
.wt-title{font-size:12.5px;font-weight:700}
.wt-loc{font-size:11px;color:var(--text3);margin-top:1px}
.wt-contact{font-size:12.5px;font-weight:700;color:var(--green)}
.btn-call-sm{padding:5px 13px;border-radius:20px;background:var(--green);color:#FFFFFF;font-size:12px;font-weight:700;border:none;transition:all .2s;text-decoration:none;display:inline-block}
.btn-call-sm:hover{background:var(--green-dark)}

/* ──── ANALYTICS ──── */
.ana-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:4px}
.ana-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px}
.ana-title{font-size:13px;font-weight:700;margin-bottom:14px;color:var(--text)}
.bar-row{margin-bottom:12px}
.bar-top{display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;color:var(--text2)}
.bar-track{height:8px;background:var(--surface2);border-radius:5px;overflow:hidden}
.bar-fill{height:100%;border-radius:5px;background:linear-gradient(90deg,var(--gold),#D4A030);transition:width .7s ease}
.ana-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.ana-row:last-child{border:none}
.ana-k{color:var(--text3)}
.ana-v{font-weight:700}
.ana-v.gn{color:var(--green)}
.ana-v.go{color:var(--gold)}
.ana-v.rd{color:var(--red)}

/* ──── TOAST ──── */
.toast-wrap{position:fixed;bottom:18px;right:18px;display:flex;flex-direction:column;gap:8px;z-index:500;pointer-events:none;max-width:320px}
.toast{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:12px 15px;font-size:13px;box-shadow:var(--shm);display:flex;align-items:center;gap:9px;pointer-events:all;animation:slideIn .25s ease;border-left:4px solid var(--green)}
.toast.tw{border-left-color:var(--gold)}
.toast.te{border-left-color:var(--red)}
.toast-icon{font-size:15px;flex-shrink:0}
@keyframes slideIn{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}

/* ──── MOBILE BOTTOM NAV ──── */
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:var(--navy);border-top:1px solid rgba(255,255,255,.1);z-index:100;padding:6px 0 env(safe-area-inset-bottom,6px)}
.mob-nav-inner{display:flex;align-items:center;justify-content:space-around}
.mn-btn{display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 12px;background:none;border:none;color:rgba(255,255,255,.5);font-size:10px;font-weight:600;min-width:60px}
.mn-btn .mn-icon{font-size:19px;line-height:1}
.mn-btn.act{color:#E8B845}
.mn-badge{font-size:9px;background:var(--red);color:#FFFFFF;padding:1px 5px;border-radius:20px;position:absolute;top:-3px;right:-3px;font-weight:700}
.mn-icon-wrap{position:relative;display:inline-block}

@media(max-width:768px){
  .sb{display:none}
  .mob-nav{display:block}
  .content{padding-bottom:80px}
  .leads-grid{grid-template-columns:1fr}
  .stats-row{grid-template-columns:repeat(3,1fr)}
  .sc-val{font-size:20px}
  .tb-search{width:120px}
  .filter-bar{display:none}
  .slide-panel{width:100vw}
  .wt-head,.wt-row{grid-template-columns:1.5fr 1fr 1fr}
  .wt-head>:nth-child(n+4),.wt-row>:nth-child(n+4){display:none}
  .ana-grid{grid-template-columns:1fr}
}
@media(max-width:480px){
  .stats-row{grid-template-columns:1fr 1fr}
  .tb-search{display:none}
}

/* Hidden logout form */
.logout-form{display:inline}
</style>
<!-- ════════════════════ HTML ════════════════════ -->
<div class="dash" id="dash">
  <aside class="sb" id="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-mark">LM</div>
      <div><div class="sb-logo-text">LawMillion</div><div class="sb-logo-sub">Attorney Dashboard</div></div>
    </div>
    <div style="flex:1;overflow-y:auto">
      <div class="ns">Leads</div>
      <button class="nb act" data-view="marketplace" onclick="gv('marketplace',this)"><span class="ni">◈</span><span class="nl">Lead Marketplace</span><span class="nbadge nb-gold" id="nc-open">0</span></button>
      <button class="nb" data-view="mybids" onclick="gv('mybids',this)"><span class="ni">◇</span><span class="nl">My Bids</span><span class="nbadge nb-gold" id="nc-mybids">0</span><span class="nbadge nb-red" id="nc-outbid" style="display:none">!</span></button>
      <button class="nb" data-view="won" onclick="gv('won',this)"><span class="ni">★</span><span class="nl">Won Leads</span><span class="nbadge nb-green" id="nc-won">0</span></button>
      <div class="ns">Practice Areas</div>
      <button class="nb" onclick="setArea('Personal Injury')"><span class="ni">·</span><span class="nl">Personal Injury</span><span class="nbadge nb-gold" id="nc-pi"></span></button>
      <button class="nb" onclick="setArea('Criminal Defense')"><span class="ni">·</span><span class="nl">Criminal Defense</span><span class="nbadge nb-gold" id="nc-cd"></span></button>
      <button class="nb" onclick="setArea('Family Law')"><span class="ni">·</span><span class="nl">Family Law</span><span class="nbadge nb-gold" id="nc-fl"></span></button>
      <button class="nb" onclick="setArea('Immigration')"><span class="ni">·</span><span class="nl">Immigration</span><span class="nbadge nb-gold" id="nc-im"></span></button>
      <button class="nb" onclick="setArea('Employment Law')"><span class="ni">·</span><span class="nl">Employment Law</span><span class="nbadge nb-gold" id="nc-el"></span></button>
      <div class="ns">Account</div>
      <button class="nb" data-view="analytics" onclick="gv('analytics',this)"><span class="ni">◉</span><span class="nl">Analytics</span></button>
      <button class="nb" onclick="toast('Billing — coming soon','w')"><span class="ni">◦</span><span class="nl">Billing &amp; Wallet</span></button>
      <a class="nb" href="<?= $attorney ? '/attorneys/' . e(strtolower((string)($attorney['state_name'] ?? 'tx'))) . '/' . e(strtolower((string)($attorney['city_name'] ?? 'dallas'))) . '/' . e($attorney['slug']) . '/' : '/for-attorneys/create-profile/' ?>" target="_blank" rel="noopener" style="text-decoration:none"><span class="ni">◦</span><span class="nl">My Public Profile</span></a>
      <form method="post" action="/for-attorneys/logout/" class="logout-form" style="display:block">
        <input type="hidden" name="<?= e(CSRF_TOKEN_NAME) ?>" value="<?= e($csrf) ?>">
        <button type="submit" class="nb"><span class="ni">⟶</span><span class="nl">Log out</span></button>
      </form>
    </div>
    <div class="sb-bottom">
      <div class="sb-avatar"><?= e($me['initials']) ?></div>
      <div>
        <div class="sb-atty-name"><?= e($me['name']) ?></div>
        <div class="sb-atty-plan"><?= e($me['plan']) ?> Plan · $<span id="sb-balance"><?= number_format($me['balance'], 0) ?></span> wallet</div>
      </div>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div class="tb-title" id="view-title">Lead Marketplace</div>
      <input type="text" id="srch" class="tb-search" placeholder="Search leads..." oninput="render()">
      <div class="tb-right">
        <div class="wallet-pill" id="wallet-pill" onclick="toast('Wallet: $' + ME.balance.toFixed(2) + ' available','s')">◈ $<span id="wallet-balance"><?= number_format($me['balance'], 2) ?></span></div>
        <button class="tb-icon-btn" onclick="toggleDark()" title="Toggle dark mode">◑</button>
        <div style="display:flex;align-items:center;gap:7px;padding:5px 12px;background:var(--surface2);border-radius:var(--rs);font-size:13px">
          <div class="sb-avatar" style="width:24px;height:24px;font-size:9px"><?= e($me['initials']) ?></div><?= e($me['firstName']) ?>
        </div>
      </div>
    </div>

    <!-- LIVE TICKER -->
    <div class="ticker" id="ticker">
      <div class="ticker-label">⚡ Live</div>
      <div class="ticker-track"><div class="ticker-inner" id="ticker-inner"></div></div>
    </div>

    <div class="content" id="content"></div>
  </div>
</div>

<!-- SLIDE PANEL -->
<div class="panel-overlay" id="panel-overlay" onclick="closePanelBg(event)">
  <div class="slide-panel">
    <div class="ph">
      <div class="ph-left">
        <div class="ph-title">Lead Details</div>
        <div class="ph-sub" id="ph-sub"></div>
      </div>
      <div class="ph-right">
        <span class="ph-timer" id="ph-timer"></span>
        <button class="ph-close" onclick="closePanel()">✕</button>
      </div>
    </div>
    <div class="pb" id="pb"></div>
  </div>
</div>

<!-- BID CONFIRM MODAL -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <div class="modal-icon">⚖️</div>
    <div class="modal-title">Confirm Your Bid</div>
    <div class="modal-sub">Review your bid before submitting. Once placed, your bid is visible to all other attorneys immediately.</div>
    <div class="modal-detail" id="modal-detail"></div>
    <div class="modal-btns">
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      <button class="modal-confirm" id="modal-confirm-btn">Confirm Bid →</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast-wrap" id="twrap"></div>

<!-- MOBILE BOTTOM NAV -->
<nav class="mob-nav">
  <div class="mob-nav-inner">
    <button class="mn-btn act" onclick="gv('marketplace',this)"><span class="mn-icon-wrap"><span class="mn-icon">◈</span><span class="mn-badge" id="mnc-open" style="display:none"></span></span>Market</button>
    <button class="mn-btn" onclick="gv('mybids',this)"><span class="mn-icon-wrap"><span class="mn-icon">◇</span><span class="mn-badge" id="mnc-out" style="display:none"></span></span>My Bids</button>
    <button class="mn-btn" onclick="gv('won',this)"><span class="mn-icon-wrap"><span class="mn-icon">★</span></span>Won</button>
    <button class="mn-btn" onclick="gv('analytics',this)"><span class="mn-icon-wrap"><span class="mn-icon">◉</span></span>Stats</button>
  </div>
</nav>

<script>
/* ══ SERVER-INJECTED STATE ══ */
let ME    = <?= json_encode($me,    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
let LEADS = <?= json_encode($leads, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
const CSRF        = <?= json_encode($csrf) ?>;
const CSRF_FIELD  = <?= json_encode(CSRF_TOKEN_NAME) ?>;
const PROFILE_OK  = <?= $attorney ? 'true' : 'false' ?>;

/* ══ STATE ══ */
let V="marketplace",AF="All",UF="All",SF="timer",panelId=null,pendingBid=null;

/* ══ HELPERS ══ */
const topBid=l=>l.bids.length?Math.max(...l.bids.map(b=>b.a)):0;
const myBid=l=>l.bids.find(b=>b.m);
const minBid=l=>Math.max(5,topBid(l)+1);
const iWon=l=>!!(l.cN && l.cN.length);   /* Server only fills cN/cP/cE if I won */
const isLeading=l=>{const mb=myBid(l);return mb&&mb.a===topBid(l);};

function fmtT(s){if(s<=0)return"Closed";const m=Math.floor(s/60),sec=s%60;if(m>=60)return`${Math.floor(m/60)}h ${m%60}m`;return`${m}:${String(sec).padStart(2,"0")}`;}
function tColor(s){if(s<=0)return"#888";if(s<=300)return"var(--red)";if(s<=900)return"var(--gold)";return"var(--green)";}
function tClass(s){if(s<=0)return"closed";if(s<=300)return"hot";if(s<=900)return"warm";return"cool";}
function urgTag(u){const c=u==="high"?"tag-h":u==="medium"?"tag-m":"tag-l";const l=u.charAt(0).toUpperCase()+u.slice(1);return`<span class="tag ${c}">${u==="high"?"⚡ ":""}${l}</span>`;}
function esc(s){return String(s==null?"":s).replace(/[&<>"']/g,c=>({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[c]));}

/* ══ TIMER RING SVG ══ */
function timerRing(secs,maxSecs){
  const pct=secs<=0?0:Math.min(1,secs/maxSecs);
  const r=14,c=2*Math.PI*r,off=c*(1-pct);
  const cls=tClass(secs);
  return `<div class="timer-wrap ${cls}">
    <div class="timer-ring">
      <svg class="tr-svg" width="36" height="36" viewBox="0 0 36 36">
        <circle class="tr-bg" cx="18" cy="18" r="${r}"/>
        <circle class="tr-fill" cx="18" cy="18" r="${r}" stroke-dasharray="${c}" stroke-dashoffset="${off.toFixed(2)}"/>
      </svg>
      <div class="timer-val">${secs<=0?"✕":Math.floor(secs/60)+"m"}</div>
    </div>
    <span class="timer-label">${fmtT(secs)}</span>
  </div>`;
}

/* ══ BIDDERS STACK ══ */
function bidStack(bids){
  if(!bids.length)return`<span style="font-size:12px;color:var(--text4);font-style:italic">No bids yet</span>`;
  const sorted=[...bids].sort((a,b)=>b.a-a.a).slice(0,4);
  const rest=bids.length-sorted.length;
  const avs=sorted.map(b=>`<div class="bs-av ${b.m?"mine-av":"other-av"}" title="${esc(b.m?"You":b.n)}: $${b.a}">${esc(b.i)}</div>`).join("");
  const more=rest>0?`<div class="bs-more">+${rest}</div>`:"";
  return`<div class="bidder-stack">${avs}${more}</div>`;
}
</script>
<script>
/* ══ RENDER ══ */
function render(){
  updNC();
  const el=document.getElementById("content");
  if(V==="analytics"){el.innerHTML=renderAna();return;}
  if(V==="won"){el.innerHTML=renderWon();return;}
  let list=[...LEADS];
  if(V==="mybids"){list=list.filter(l=>myBid(l)||iWon(l));}
  else{
    const q=(document.getElementById("srch")||{}).value||"";
    if(AF!=="All")list=list.filter(l=>l.area===AF);
    if(UF!=="All")list=list.filter(l=>l.urgency===UF);
    if(q)list=list.filter(l=>l.title.toLowerCase().includes(q.toLowerCase())||l.loc.toLowerCase().includes(q.toLowerCase())||l.area.toLowerCase().includes(q.toLowerCase()));
  }
  list.sort((a,b)=>{
    if(SF==="timer")return a.secs-b.secs;
    if(SF==="bid")return topBid(b)-topBid(a);
    if(SF==="urg"){const u={high:0,medium:1,low:2};return u[a.urgency]-u[b.urgency];}
    return 0;
  });
  const openL=LEADS.filter(l=>l.status==="open");
  const myA=LEADS.filter(l=>l.status==="open"&&myBid(l));
  const myW=myA.filter(l=>isLeading(l));
  const myO=myA.filter(l=>!isLeading(l));
  const myWon=LEADS.filter(l=>iWon(l));
  const spend=myWon.reduce((s,l)=>s+(myBid(l)?myBid(l).a:0),0);

  el.innerHTML=`
  ${!PROFILE_OK?`<div class="profile-banner">
    <div class="profile-banner-text">⚠ Your attorney profile is incomplete. <a href="/for-attorneys/create-profile/">Complete your profile</a> to start placing bids on leads.</div>
  </div>`:""}

  <div class="stats-row">
    <div class="sc c-gold" onclick="gv('marketplace',null)" style="cursor:pointer">
      <div class="sc-accent" style="background:var(--gold)"></div>
      <div class="sc-label">Open Leads</div><div class="sc-val">${openL.length}</div>
      <div class="sc-sub">Live bids active</div><div class="sc-icon">◈</div>
    </div>
    <div class="sc" onclick="gv('mybids',null)" style="cursor:pointer">
      <div class="sc-accent" style="background:var(--blue)"></div>
      <div class="sc-label">My Active Bids</div><div class="sc-val">${myA.length}</div>
      <div class="sc-sub">${myW.length} leading · ${myO.length} outbid</div><div class="sc-icon">◇</div>
    </div>
    <div class="sc">
      <div class="sc-accent" style="background:var(--green)"></div>
      <div class="sc-label">Currently Winning</div><div class="sc-val" style="color:var(--green)">${myW.length}</div>
      <div class="sc-sub">Highest bidder</div><div class="sc-icon">★</div>
    </div>
    <div class="sc" onclick="gv('won',null)" style="cursor:pointer">
      <div class="sc-accent" style="background:var(--green)"></div>
      <div class="sc-label">Leads Won</div><div class="sc-val" style="color:var(--green)">${myWon.length}</div>
      <div class="sc-sub">Contact unlocked</div><div class="sc-icon">✓</div>
    </div>
    <div class="sc">
      <div class="sc-accent" style="background:var(--gold)"></div>
      <div class="sc-label">Bid Spend</div><div class="sc-val">$${spend}</div>
      <div class="sc-sub">Won leads only</div><div class="sc-icon">$</div>
    </div>
  </div>

  ${myO.length>0?`<div class="outbid-bar">
    <div class="ob-text">⚠ You have been outbid on <strong>${myO.length}</strong> lead${myO.length>1?"s":""}. Raise your bid before the timers expire!</div>
    <button class="ob-btn" onclick="gv('mybids',null)">View Outbid Leads →</button>
  </div>`:""}

  ${V==="marketplace"?`
  <div class="filter-bar">
    <div class="filter-row">
      <span class="fl">Area:</span>
      ${["All","Personal Injury","Criminal Defense","Family Law","Immigration","Employment Law","Tax Law"].map(a=>`<button class="chip${AF===a?" ac":""}" onclick="AF='${a}';render()">${a}</button>`).join("")}
    </div>
    <div class="filter-row">
      <span class="fl">Urgency:</span>
      <button class="chip${UF==="All"?" ac":""}" onclick="UF='All';render()">All</button>
      <button class="chip uh${UF==="high"?" ac":""}" onclick="UF='high';render()">⚡ High</button>
      <button class="chip um${UF==="medium"?" ac":""}" onclick="UF='medium';render()">Medium</button>
      <button class="chip ul${UF==="low"?" ac":""}" onclick="UF='low';render()">Low</button>
      <div style="margin-left:auto;display:flex;gap:6px;align-items:center">
        <span class="fl">Sort:</span>
        ${[["timer","Timer"],["bid","Top Bid"],["urg","Urgency"]].map(([k,v])=>`<button class="sb-btn${SF===k?" ac":""}" onclick="SF='${k}';render()">${v}</button>`).join("")}
      </div>
    </div>
  </div>`:""}

  ${V==="mybids"?`<div style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--text2)">
    Your bids — ${list.length} lead${list.length!==1?"s":""} &nbsp;·&nbsp;
    <span style="color:var(--green)">${myW.length} leading</span> &nbsp;·&nbsp;
    <span style="color:var(--red)">${myO.length} outbid</span>
  </div>`:""}

  <div class="leads-grid">
    ${list.length===0?`<div class="empty-state"><div class="empty-icon">◈</div><div class="empty-text">${V==="mybids"?"No active bids. Go to the Marketplace to find leads.":"No leads match your filters."}</div></div>`:""}
    ${list.map(l=>renderCard(l)).join("")}
  </div>`;
}

/* ══ LEAD CARD ══ */
function renderCard(l){
  const tb=topBid(l),mb=myBid(l),mn=minBid(l);
  const lead=isLeading(l),won=l.status!=="open",iw=iWon(l);
  const cls=iw?"lc-won":won?"lc-closed":lead?"lc-leading":mb?"lc-outbid":"";
  const bandColor=iw||lead?"var(--green)":mb&&!lead?"var(--red)":tb>0?"var(--gold)":"var(--border)";
  const inp=`inp-${l.id}`;

  return`<div class="lc ${cls}" id="card-${l.id}">
    <div class="lc-band" style="background:${bandColor}"></div>
    <div class="lc-head">
      <div class="lc-head-row1">
        <div class="lc-title">${esc(l.title)}</div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0">
          ${urgTag(l.urgency)}
          ${iw?`<span class="tag tag-won">★ Won</span>`:""}
          ${won&&!iw?`<span class="tag tag-cl">Closed</span>`:""}
        </div>
      </div>
      <div class="lc-tags">
        <span class="tag tag-area">${esc(l.area)}</span>
        <span class="tag tag-sub">${esc(l.sub)}</span>
        <span class="tag tag-val">💰 ${esc(l.value)}</span>
      </div>
      <div class="lc-meta">
        <span>📍 ${esc(l.loc)}</span><span>📅 ${esc(l.date)}</span><span>${esc(l.budget)}</span>
      </div>
    </div>

    <div class="lc-desc">
      <div class="lc-desc-txt">${esc(l.desc)}</div>
      <div class="lc-facts">${l.facts.slice(0,3).map(f=>`<span class="fc">${esc(f)}</span>`).join("")}</div>
    </div>

    ${lead&&!won?`<div class="lc-leading-banner">★ You are the leading bidder at <strong>$${mb.a}</strong> — hold your position! Timer: ${fmtT(l.secs)}</div>`:""}
    ${mb&&!lead&&!won?`<div class="lc-outbid-banner">
      <span class="lob-text">⚠ Outbid! Current top: <strong>$${tb}</strong></span>
      <button class="lob-raise" onclick="event.stopPropagation();quickRaise('${l.id}')">Raise +$5 →</button>
    </div>`:""}

    <div class="lc-bid-bar">
      <div class="bb-item">
        <div class="bb-lbl">Top Bid</div>
        <div class="bb-val ${tb>0?"c-gold":"c-muted"}">${tb>0?`$${tb}`:"No bids"}</div>
        <div class="bb-sub">${bidStack(l.bids)}</div>
      </div>
      <div class="bb-item">
        <div class="bb-lbl">My Bid</div>
        <div class="bb-val ${mb?lead?"c-green":"c-red":"c-muted"}">${mb?`$${mb.a}`:"—"}</div>
        <div class="bb-sub" style="color:${mb?lead?"var(--green)":"var(--red)":"var(--text4)"}">
          ${mb?(lead?"★ Leading":"⚠ Outbid!"):"Not placed"}
          ${mb?`· #${[...l.bids].sort((a,b)=>b.a-a.a).findIndex(b=>b.m)+1} of ${l.bids.length}`:""}
        </div>
      </div>
      <div class="bb-item" style="display:flex;flex-direction:column;align-items:flex-end">
        <div class="bb-lbl" style="text-align:right">Time Left</div>
        ${timerRing(l.secs,1800)}
      </div>
    </div>

    <div class="lc-contact${iw?" uc":""}">
      <div class="ci"><span class="ci-icon">📞</span>${iw?`<span class="ci-val">${esc(l.cP)}</span>`:`<span class="ci-mask">●●●-●●●-●●●●</span>`}</div>
      <div class="ci"><span class="ci-icon">✉</span>${iw?`<span class="ci-val" style="font-size:12px">${esc(l.cE)}</span>`:`<span class="ci-mask">●●●@●●●.●●●</span>`}</div>
      ${!iw?`<span class="win-note">Win to unlock</span>`:""}
    </div>

    <div class="lc-actions">
      ${won?(iw?`
        <a class="btn-contact-card" href="tel:${esc(l.cP.replace(/[^0-9+]/g,""))}">📞 Call ${esc(l.cN.split(" ")[0])}</a>
        <button class="btn-details" onclick="openPanel('${l.id}')">Details</button>
      `:`<div style="flex:1;text-align:center;font-size:12px;color:var(--text3)">Closed${l.wonBy?" — won by "+esc(l.wonBy.split(" ")[0]):""}</div><button class="btn-details" onclick="openPanel('${l.id}')">View</button>`)
      :(l.bids.length===0?`
        <button class="btn-first-bid" onclick="prefillAndOpen('${l.id}',5)">🎯 Be First to Bid — Min $5</button>
        <button class="btn-details" onclick="openPanel('${l.id}')">Details</button>
      `:`
        <div class="bid-quick-row">
          ${[mn,mn+5,mn+10].map(v=>`<button class="bq" id="bq-${l.id}-${v}" onclick="setCard('${l.id}','${inp}',${v})">\$${v}</button>`).join("")}
        </div>
        <div class="bid-input-group">
          <span class="bid-dollar">$</span>
          <input type="number" class="bid-inp" id="${inp}" min="${mn}" step="1" value="${mb?mb.a+1:mn}"
            onkeydown="if(event.key==='Enter')askConfirm('${l.id}','${inp}')"
            oninput="syncQuick('${l.id}','${inp}')">
        </div>
        <button class="btn-bid" onclick="askConfirm('${l.id}','${inp}')">Bid →</button>
        <button class="btn-details" onclick="openPanel('${l.id}')">Details</button>
      `)}
    </div>
  </div>`;
}

function setCard(lid,inp,v){
  const el=document.getElementById(inp);
  if(el)el.value=v;
  syncQuick(lid,inp);
}
function syncQuick(lid,inp){
  const v=parseInt((document.getElementById(inp)||{}).value||0);
  document.querySelectorAll(`[id^="bq-${lid}-"]`).forEach(b=>{
    const bv=parseInt(b.id.split("-").pop());
    b.classList.toggle("bq-ac",bv===v);
  });
}
function quickRaise(lid){
  const l=LEADS.find(x=>x.id===lid);
  if(!l)return;
  const mb=myBid(l);
  const newAmt=Math.max(minBid(l),(mb?mb.a:0)+5);
  askConfirm(lid,null,newAmt);
}
function prefillAndOpen(lid,amt){
  openPanel(lid);
  setTimeout(()=>{
    const el=document.getElementById(`bp-inp-${lid}`);
    if(el){el.value=amt;highlightBPQ(lid,amt);}
  },120);
}

/* ══ BID CONFIRM MODAL ══ */
function askConfirm(lid,inputId,overrideAmt){
  if(!PROFILE_OK){toast("Complete your attorney profile to bid.","e");return;}
  const l=LEADS.find(x=>x.id===lid);
  if(!l||l.status!=="open"){toast("This lead is already closed","e");return;}
  const inp=inputId?document.getElementById(inputId):null;
  const amt=overrideAmt!==undefined?overrideAmt:parseInt(inp?inp.value:minBid(l));
  const mn=minBid(l);
  if(isNaN(amt)||amt<mn){toast(`Minimum bid is $${mn}`,"e");if(inp)inp.focus();return;}
  const tb=topBid(l);
  pendingBid={lid,amt};
  document.getElementById("modal-detail").innerHTML=`
    <div class="modal-detail-row"><span class="mk">Lead</span><span class="mv" style="font-size:12px;text-align:right;max-width:60%">${esc(l.title.split("—")[0].trim())}</span></div>
    <div class="modal-detail-row"><span class="mk">Your bid</span><span class="mv gold">$${amt}</span></div>
    <div class="modal-detail-row"><span class="mk">Current top bid</span><span class="mv">${tb>0?"$"+tb:"No bids yet"}</span></div>
    <div class="modal-detail-row"><span class="mk">Minimum bid</span><span class="mv">$${mn}</span></div>
    <div class="modal-detail-row"><span class="mk">Timer resets to</span><span class="mv">30:00</span></div>
    <div class="modal-detail-row"><span class="mk">Wallet charge</span><span class="mv">Only if you win</span></div>
  `;
  document.getElementById("modal-overlay").classList.add("open");
  const btn=document.getElementById("modal-confirm-btn");
  btn.disabled=false;
  btn.textContent="Confirm Bid →";
  btn.onclick=confirmBid;
}

async function confirmBid(){
  if(!pendingBid)return;
  const {lid,amt}=pendingBid;
  const btn=document.getElementById("modal-confirm-btn");
  btn.disabled=true;
  btn.textContent="Placing…";
  try{
    const fd=new FormData();
    fd.append("lead_id",lid);
    fd.append("amount",String(amt));
    fd.append(CSRF_FIELD,CSRF);
    const r=await fetch("/for-attorneys/dashboard/place-bid",{method:"POST",body:fd,credentials:"same-origin"});
    const data=await r.json().catch(()=>({ok:false,error:"Network error"}));
    if(!r.ok||!data.ok){
      toast(data.error||"Could not place bid","e");
      btn.disabled=false;
      btn.textContent="Confirm Bid →";
      return;
    }
    closeModal();
    applyState(data.state);
    toast(data.message||`Bid of $${amt} placed!`);
  }catch(e){
    toast("Network error. Please try again.","e");
    btn.disabled=false;
    btn.textContent="Confirm Bid →";
  }
}
function closeModal(){document.getElementById("modal-overlay").classList.remove("open");pendingBid=null;}
document.getElementById("modal-overlay").addEventListener("click",e=>{if(e.target===document.getElementById("modal-overlay"))closeModal();});

/* ══ PANEL ══ */
function openPanel(lid){
  panelId=lid;
  document.getElementById("panel-overlay").classList.add("open");
  renderPanel(lid);
}
function closePanel(){document.getElementById("panel-overlay").classList.remove("open");panelId=null;}
function closePanelBg(e){if(e.target===document.getElementById("panel-overlay"))closePanel();}
document.addEventListener("keydown",e=>{if(e.key==="Escape"){closeModal();closePanel();}});

function renderPanel(lid){
  const l=LEADS.find(x=>x.id===lid);
  if(!l){closePanel();return;}
  const tb=topBid(l),mb=myBid(l),mn=minBid(l);
  const lead=isLeading(l),won=l.status!=="open",iw=iWon(l);
  const tc=tColor(l.secs);
  const myPos=mb?[...l.bids].sort((a,b)=>b.a-a.a).findIndex(b=>b.m)+1:-1;
  document.getElementById("ph-sub").textContent=`${l.area} · ${l.loc}`;
  document.getElementById("ph-timer").innerHTML=`<span style="color:${tc};font-weight:700">${won?"Closed":"⏱ "+fmtT(l.secs)}</span>`;
  const defBid=Math.max(mn,mb?mb.a+1:5);
  const qAmts=[...new Set([mn,mn+5,mn+10,mn+25,mn+50])];

  document.getElementById("pb").innerHTML=`

    ${iw?`<div class="sb-banner sb-won"><span class="sb-icon">★</span><div><strong>You won this lead!</strong><br>Full client contact is unlocked below. Contact the client to begin intake.</div></div>`:""}
    ${!won&&lead?`<div class="sb-banner sb-leading"><span class="sb-icon">★</span><div><strong>You are leading at $${mb.a}</strong> — position #1 of ${l.bids.length}.<br>${l.secs>0?`If no higher bid arrives in the next <strong>${fmtT(l.secs)}</strong>, you win and client contact unlocks.`:"Timer almost up — almost yours!"}</div></div>`:""}
    ${!won&&mb&&!lead?`<div class="sb-banner sb-outbid"><span class="sb-icon">⚠</span><div><strong>You have been outbid!</strong> You are #${myPos} of ${l.bids.length} bidders.<br>Top bid: <strong>$${tb}</strong> · Raise your bid to <strong>$${mn}</strong> or more to retake the lead.</div></div>`:""}

    <div class="ps">
      <div class="panel-title-text">${esc(l.title)}</div>
      <div class="panel-tags">${urgTag(l.urgency)}<span class="tag tag-area">${esc(l.area)}</span><span class="tag tag-sub">${esc(l.sub)}</span><span class="tag tag-val">💰 ${esc(l.value)}</span>${iw?`<span class="tag tag-won">★ Won</span>`:""}</div>
    </div>

    ${!won?`
    <div class="ps">
      <div class="ps-hdr"><div class="ps-title">Place Your Bid</div>${mb?`<span class="ps-badge" style="background:${lead?"var(--green-bg)":"var(--red-bg)"};color:${lead?"var(--green)":"var(--red)"}">My current bid: $${mb.a} (${lead?"Leading":"Outbid"})</span>`:`<span class="ps-badge" style="background:var(--surface2);color:var(--text3)">No bid placed</span>`}</div>
      <div class="bid-panel-box">
        <div class="bpb-header">
          <span>Min: <strong>$${mn}</strong></span>
          <span>Top bid: <strong style="color:var(--gold)">${tb>0?"$"+tb:"None yet"}</strong></span>
          <span>Timer: <strong style="color:${tc}">${fmtT(l.secs)}</strong></span>
        </div>
        <div class="bpb-row">
          <span class="bpb-dollar">$</span>
          <input type="number" id="bp-inp-${l.id}" class="bpb-input" min="${mn}" step="1" value="${defBid}"
            onkeydown="if(event.key==='Enter')askConfirm('${l.id}','bp-inp-${l.id}')"
            oninput="highlightBPQ('${l.id}',this.value)">
          <button class="bpb-btn" onclick="askConfirm('${l.id}','bp-inp-${l.id}')">Confirm Bid →</button>
        </div>
        <div style="font-size:12px;color:var(--text3);font-weight:600;margin-bottom:7px">Quick amounts:</div>
        <div class="bpb-quick">
          ${qAmts.map(v=>`<button class="bpb-q${defBid===v?" ac":""}" data-bpq="${l.id}" data-amt="${v}" onclick="setBPQ('${l.id}',${v})">\$${v}</button>`).join("")}
        </div>
        <div class="bpb-note">
          <strong>⚡ How bidding works:</strong> Place any amount at or above the minimum.
          All attorneys see each bid in real time. The 30-minute timer resets with every new bid.
          Highest bidder when timer reaches zero wins the lead — client contact details unlock instantly.
          <strong>You only pay if you win.</strong>
        </div>
      </div>
    </div>`:""}

    <div class="ps">
      <div class="ps-title">Case Description</div>
      <div class="p-desc">${esc(l.desc)}</div>
    </div>

    <div class="ps">
      <div class="ps-title">Key Case Facts</div>
      <div class="p-facts">${l.facts.map(f=>`<div class="p-fact"><span class="pf-ck">✓</span>${esc(f)}</div>`).join("")}</div>
    </div>

    <div class="ps">
      <div class="ps-title">Lead Information</div>
      <div class="p-info-table">
        ${[["Location",l.loc,""],["Practice area",`${l.area} — ${l.sub}`,""],["Urgency",l.urgency.charAt(0).toUpperCase()+l.urgency.slice(1),""],["Client budget",l.budget,""],["Estimated value",l.value,"gold"],["Lead date",l.date,""],["Lead ID",`#LM-${l.id}`,""],["Fee structure",l.budget==="Contingency"?"No win no fee":"Fixed / Hourly",""]].map(([k,v,c])=>`
          <div class="p-info-row"><span class="p-info-k">${esc(k)}</span><span class="p-info-v ${c}">${esc(v)}</span></div>`).join("")}
      </div>
    </div>

    <div class="ps">
      <div class="ps-hdr">
        <div class="ps-title">Client Contact</div>
        <span class="ps-badge" style="background:${iw?"var(--green-bg)":"var(--surface2)"};color:${iw?"var(--green)":"var(--text4)"}">${iw?"🔓 Unlocked":"🔒 Win to reveal"}</span>
      </div>
      ${iw?`<div class="p-contact-unlocked p-info-table">
        <div class="p-info-row"><span class="p-info-k">Client name</span><span class="p-info-v green">${esc(l.cN)}</span></div>
        <div class="p-info-row"><span class="p-info-k">Phone number</span><span class="p-info-v green">${esc(l.cP)}</span></div>
        <div class="p-info-row"><span class="p-info-k">Email address</span><span class="p-info-v green">${esc(l.cE)}</span></div>
      </div>
      <div class="pca">
        <a class="btn-call-p" href="tel:${esc(l.cP.replace(/[^0-9+]/g,""))}">📞 Call ${esc(l.cN.split(" ")[0])}</a>
        <a class="btn-email-p" href="mailto:${esc(l.cE)}">✉ Email Client</a>
      </div>`:`<div class="p-info-table">
        <div class="p-info-row"><span class="p-info-k">Client name</span><span class="p-mask">●●●●●●●●●●●</span></div>
        <div class="p-info-row"><span class="p-info-k">Phone number</span><span class="p-mask">●●●-●●●-●●●●</span></div>
        <div class="p-info-row"><span class="p-info-k">Email address</span><span class="p-mask">●●●●@●●●●.●●●</span></div>
      </div>
      <div style="margin-top:9px;font-size:12px;color:var(--text3);background:var(--surface2);padding:10px 13px;border-radius:var(--rs);line-height:1.65">
        Client details are instantly revealed when you win this lead. To win: place the highest bid before the timer reaches zero.
      </div>`}
    </div>

    <div class="ps">
      <div class="ps-hdr">
        <div class="ps-title">Live Bid Board</div>
        <span class="ps-badge" style="background:var(--gold-bg);color:var(--gold-text)">${l.bids.length} bid${l.bids.length!==1?"s":""} · Visible to all</span>
      </div>
      ${l.bids.length===0?`<div class="no-bids-msg">No bids yet — be the first. Minimum bid: $5</div>`:`
      <div class="bid-board">
        ${[...l.bids].sort((a,b)=>b.a-a.a).map((b,i)=>`
          <div class="bid-row ${b.m?"br-mine":""} ${i===0?"br-top":""}">
            <div class="bid-av ${b.m?"bav-mine":"bav-other"}">${esc(b.i)}</div>
            <div class="bid-info">
              <div class="bid-name">${esc(b.m?`You (${ME.firstName})`:b.n)}${i===0?" ★":""}</div>
              <div class="bid-time">${esc(b.t)}${i===0?" · Leading":""}</div>
            </div>
            <div style="display:flex;align-items:center">
              <span class="bid-amt" style="color:${i===0?"var(--gold)":"var(--text2)"}">$${b.a}</span>
              <span class="bid-rank ${i===0?"br1":"br2"}">#${i+1}</span>
            </div>
          </div>`).join("")}
      </div>
      <div style="margin-top:9px;font-size:12px;color:var(--text3);display:flex;gap:14px;flex-wrap:wrap">
        <span>Min next bid: <strong style="color:var(--text)">$${mn}</strong></span>
        <span>Your position: <strong style="color:${mb?isLeading(l)?"var(--green)":"var(--red)":"var(--text4)"}">${mb?"#"+myPos+" of "+l.bids.length:"Not bidding"}</strong></span>
        <span>${l.bids.length} attorney${l.bids.length!==1?"s":""} competing</span>
      </div>`}
    </div>

    ${won&&!iw?`<div class="sb-banner sb-outbid"><span class="sb-icon">✕</span><div>This lead was closed${l.wonBy?" and won by <strong>"+esc(l.wonBy)+"</strong>":""}.</div></div>`:""}
    <div style="height:24px"></div>
  `;
}

function setBPQ(lid,v){
  const el=document.getElementById(`bp-inp-${lid}`);
  if(el)el.value=v;
  highlightBPQ(lid,v);
}
function highlightBPQ(lid,v){
  document.querySelectorAll(`[data-bpq="${lid}"]`).forEach(b=>{
    b.classList.toggle("ac",parseInt(b.dataset.amt)===parseInt(v));
  });
}

/* ══ WON VIEW ══ */
function renderWon(){
  const won=LEADS.filter(l=>iWon(l));
  const spend=won.reduce((s,l)=>s+(myBid(l)?myBid(l).a:0),0);
  return`
  <div class="stats-row">
    <div class="sc"><div class="sc-accent" style="background:var(--green)"></div><div class="sc-label">Won</div><div class="sc-val" style="color:var(--green)">${won.length}</div><div class="sc-sub">Total</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--gold)"></div><div class="sc-label">Total Spend</div><div class="sc-val">$${spend}</div><div class="sc-sub">Bid costs</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--blue)"></div><div class="sc-label">Avg Bid</div><div class="sc-val">$${won.length?Math.round(spend/won.length):0}</div><div class="sc-sub">Per lead</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--green)"></div><div class="sc-label">Wallet</div><div class="sc-val">$${ME.balance.toFixed(0)}</div><div class="sc-sub">Available</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--green)"></div><div class="sc-label">Pending Contact</div><div class="sc-val">${won.length}</div><div class="sc-sub">Awaiting outreach</div></div>
  </div>
  <div style="font-size:14px;font-weight:700;margin-bottom:12px">Your Won Leads — Full Client Details Unlocked</div>
  <div class="won-section">
    <div class="wt-head"><div>Lead</div><div>Area</div><div>Bid</div><div>Client Name</div><div>Phone</div><div>Email</div><div>Action</div></div>
    ${won.length===0?`<div style="padding:32px;text-align:center;color:var(--text3)">No won leads yet. Go to the Marketplace and start bidding!</div>`:""}
    ${won.map(l=>`<div class="wt-row" onclick="openPanel('${l.id}')">
      <div><div class="wt-title">${esc(l.title.split("—")[0].trim())}</div><div class="wt-loc">📍 ${esc(l.loc)}</div></div>
      <div><span class="tag tag-area" style="font-size:10.5px">${esc(l.sub)}</span></div>
      <div style="font-weight:700;color:var(--gold)">$${myBid(l)?myBid(l).a:0}</div>
      <div style="font-weight:700">${esc(l.cN)}</div>
      <div class="wt-contact">${esc(l.cP)}</div>
      <div class="wt-contact" style="font-size:11.5px">${esc(l.cE)}</div>
      <div><a class="btn-call-sm" href="tel:${esc(l.cP.replace(/[^0-9+]/g,""))}" onclick="event.stopPropagation()">📞 Call</a></div>
    </div>`).join("")}
  </div>`;
}

/* ══ ANALYTICS ══ */
function renderAna(){
  const won=LEADS.filter(l=>iWon(l));
  const myA=LEADS.filter(l=>l.status==="open"&&myBid(l));
  const myW=myA.filter(l=>isLeading(l));
  const myO=myA.filter(l=>!isLeading(l));
  const spend=won.reduce((s,l)=>s+(myBid(l)?myBid(l).a:0),0);
  const closed=LEADS.filter(l=>l.status!=="open");
  const wr=closed.length?Math.round(won.length/closed.length*100):0;
  const areas={};LEADS.forEach(l=>{areas[l.area]=(areas[l.area]||0)+1;});
  const maxA=Math.max(...Object.values(areas),1);
  return`
  <div class="stats-row">
    <div class="sc"><div class="sc-accent" style="background:var(--green)"></div><div class="sc-label">Win Rate</div><div class="sc-val" style="color:var(--green)">${wr}%</div><div class="sc-sub">vs. all closed</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--green)"></div><div class="sc-label">Leads Won</div><div class="sc-val">${won.length}</div><div class="sc-sub">Total</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--gold)"></div><div class="sc-label">Total Spend</div><div class="sc-val">$${spend}</div><div class="sc-sub">Won leads only</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--blue)"></div><div class="sc-label">Avg Win Bid</div><div class="sc-val">$${won.length?Math.round(spend/won.length):0}</div><div class="sc-sub">Per won lead</div></div>
    <div class="sc"><div class="sc-accent" style="background:var(--gold)"></div><div class="sc-label">Wallet</div><div class="sc-val">$${ME.balance.toFixed(0)}</div><div class="sc-sub">Balance</div></div>
  </div>
  <div class="ana-grid">
    <div class="ana-card">
      <div class="ana-title">Leads by practice area</div>
      ${Object.entries(areas).map(([a,c])=>`<div class="bar-row"><div class="bar-top"><span>${esc(a)}</span><strong>${c}</strong></div><div class="bar-track"><div class="bar-fill" style="width:${Math.round(c/maxA*100)}%"></div></div></div>`).join("")}
    </div>
    <div class="ana-card">
      <div class="ana-title">My bidding activity</div>
      ${[["Total bids placed",LEADS.filter(l=>myBid(l)).length,""],["Currently leading",myW.length,"gn"],["Outbid — needs action",myO.length,myO.length?"rd":""],["Leads won (total)",won.length,"gn"],["Bid spend (wins)","$"+spend,"go"],["Avg bid to win","$"+(won.length?Math.round(spend/won.length):0),""],["Wallet balance","$"+ME.balance.toFixed(0),"gn"]].map(([k,v,c])=>`<div class="ana-row"><span class="ana-k">${esc(k)}</span><span class="ana-v ${c}">${esc(v)}</span></div>`).join("")}
    </div>
  </div>`;
}

/* ══ TICKER ══ */
let tickerItems=[];
const seenBidIds=new Set();
function addTicker({name,amt,lead,mine=false}){
  const dot=`<div class="ticker-dot" style="background:${mine?"var(--gold)":"var(--text4)"}"></div>`;
  const text=`${esc(mine?"You":name)} bid $${amt} on ${esc(lead)}`;
  const cls=mine?"ticker-item mine":"ticker-item";
  tickerItems.unshift({dot,text,cls});
  if(tickerItems.length>20)tickerItems.pop();
  renderTicker();
}
function renderTicker(){
  const inner=tickerItems.map(t=>`<div class="${t.cls}">${t.dot}${t.text}</div>`).join("");
  const ti=document.getElementById("ticker-inner");
  if(ti)ti.innerHTML=inner+inner;
}
function seedTickerFromLeads(){
  /* Show the most recent ~12 bids across all open leads, newest first.
     Bids are server-time-ordered within each lead; flatten and slice. */
  const all=[];
  LEADS.forEach(l=>l.bids.forEach(b=>all.push({b,leadTitle:l.title.split("—")[0].trim()})));
  all.slice(0,12).reverse().forEach(({b,leadTitle})=>{
    seenBidIds.add(b.id);
    addTicker({name:b.n,amt:b.a,lead:leadTitle,mine:b.m});
  });
}

/* ══ APPLY NEW STATE FROM SERVER ══ */
function applyState(newState){
  if(!newState)return;
  const oldLeading=new Set(LEADS.filter(isLeading).map(l=>l.id));
  ME=newState.me;
  LEADS=newState.leads;

  /* Detect: was leading, now outbid */
  LEADS.forEach(l=>{
    if(oldLeading.has(l.id) && myBid(l) && !isLeading(l)){
      toast(`⚠ Outbid on "${l.title.split("—")[0].trim()}" — top bid now $${topBid(l)}.`,"w");
    }
  });

  /* Detect new bids since last poll → ticker */
  LEADS.forEach(l=>{
    l.bids.forEach(b=>{
      if(!seenBidIds.has(b.id) && !b.m){
        seenBidIds.add(b.id);
        addTicker({name:b.n,amt:b.a,lead:l.title.split("—")[0].trim()});
      } else if(!seenBidIds.has(b.id)){
        seenBidIds.add(b.id);
      }
    });
  });

  /* Refresh wallet display */
  document.getElementById("wallet-balance").textContent=ME.balance.toFixed(2);
  document.getElementById("sb-balance").textContent=Math.round(ME.balance);

  updNC();render();
  if(panelId)renderPanel(panelId);
}

/* ══ TIMER ENGINE (client-side tick — server is source of truth) ══ */
setInterval(()=>{
  let changed=false;
  LEADS.forEach(l=>{
    if(l.status!=="open"||l.secs<=0)return;
    l.secs=Math.max(0,l.secs-1);
    changed=true;
  });
  if(!changed)return;
  /* Update timer rings in place to avoid full re-render flicker */
  LEADS.forEach(l=>{
    const ring=document.querySelector(`#card-${l.id} .timer-ring`);
    const val=document.querySelector(`#card-${l.id} .timer-val`);
    const label=document.querySelector(`#card-${l.id} .timer-label`);
    const wrap=document.querySelector(`#card-${l.id} .timer-wrap`);
    if(!ring)return;
    const pct=l.secs<=0?0:Math.min(1,l.secs/1800);
    const r=14,c=2*Math.PI*r,off=(c*(1-pct)).toFixed(2);
    const fill=ring.querySelector(".tr-fill");
    if(fill)fill.style.strokeDashoffset=off;
    if(val)val.textContent=l.secs<=0?"✕":Math.floor(l.secs/60)+"m";
    if(label)label.textContent=fmtT(l.secs);
    if(wrap){wrap.className="timer-wrap "+tClass(l.secs);}
  });
  if(panelId){
    const pl=LEADS.find(x=>x.id===panelId);
    if(pl){const ph=document.getElementById("ph-timer");if(ph)ph.innerHTML=`<span style="color:${tColor(pl.secs)};font-weight:700">${pl.status!=="open"?"Closed":"⏱ "+fmtT(pl.secs)}</span>`;}
  }
},1000);

/* ══ LIVE POLLING — every 15s, fetch state from server ══ */
let pollFailures=0;
async function pollState(){
  try{
    const r=await fetch("/for-attorneys/dashboard/state",{credentials:"same-origin",cache:"no-store"});
    if(r.status===401){
      window.location.href="/for-attorneys/login/";
      return;
    }
    const data=await r.json();
    if(data.ok){
      pollFailures=0;
      applyState(data.state);
    }
  }catch(e){
    pollFailures++;
    /* Silent — timer keeps ticking client-side. Surface only on repeated failures. */
    if(pollFailures===3)toast("Connection lost — retrying…","w");
  }
}
setInterval(pollState,15000);

/* ══ NAV COUNTS ══ */
function updNC(){
  const s=(id,v)=>{const e=document.getElementById(id);if(e){e.textContent=v;e.style.display=v?"":"none";}};
  const openL=LEADS.filter(l=>l.status==="open");
  const myA=LEADS.filter(l=>l.status==="open"&&myBid(l));
  const myO=myA.filter(l=>!isLeading(l));
  const myWon=LEADS.filter(l=>iWon(l));
  s("nc-open",openL.length);s("nc-mybids",myA.length);s("nc-won",myWon.length);
  const ob=document.getElementById("nc-outbid");
  if(ob){ob.textContent=myO.length;ob.style.display=myO.length?"":"none";}
  const areas={"pi":"Personal Injury","cd":"Criminal Defense","fl":"Family Law","im":"Immigration","el":"Employment Law"};
  Object.entries(areas).forEach(([k,a])=>{const c=LEADS.filter(l=>l.area===a&&l.status==="open").length;s("nc-"+k,c||"");});
  const mnco=document.getElementById("mnc-open");
  if(mnco){mnco.textContent=openL.length;mnco.style.display=openL.length?"":"none";}
  const mnout=document.getElementById("mnc-out");
  if(mnout){mnout.textContent=myO.length||"";mnout.style.display=myO.length?"":"none";}
}

/* ══ NAVIGATION ══ */
function gv(v,btn){
  V=v;if(v==="marketplace")AF="All";
  document.querySelectorAll(".nb[data-view]").forEach(b=>b.classList.remove("act"));
  if(btn&&btn.dataset&&btn.dataset.view)btn.classList.add("act");
  else document.querySelector(`.nb[data-view="${v}"]`)?.classList.add("act");
  document.querySelectorAll(".mn-btn").forEach(b=>b.classList.remove("act"));
  document.getElementById("view-title").textContent={marketplace:"Lead Marketplace",mybids:"My Bids",won:"Won Leads",analytics:"Analytics"}[v]||v;
  document.getElementById("srch").style.display=v==="marketplace"?"":"none";
  render();closePanel();
}
function setArea(a){AF=a;gv("marketplace",null);document.getElementById("view-title").textContent=a;}

/* ══ DARK MODE — persisted ══ */
function toggleDark(){
  document.documentElement.classList.toggle("dark");
  try{localStorage.setItem("lm-dark",document.documentElement.classList.contains("dark")?"1":"0");}catch(e){}
}
try{if(localStorage.getItem("lm-dark")==="1")document.documentElement.classList.add("dark");}catch(e){}

/* ══ TOAST ══ */
function toast(msg,type="s"){
  const w=document.getElementById("twrap");
  const t=document.createElement("div");
  t.className=`toast${type==="w"?" tw":type==="e"?" te":""}`;
  t.innerHTML=`<span class="toast-icon">${type==="e"?"⚠":type==="w"?"⚡":"◈"}</span>${esc(msg)}`;
  w.appendChild(t);
  setTimeout(()=>{t.style.transition="all .3s";t.style.opacity="0";t.style.transform="translateY(10px)";setTimeout(()=>t.remove(),350);},3500);
}

/* ══ INIT ══ */
document.getElementById("srch").style.display="";
seedTickerFromLeads();
updNC();render();
</script>
</body></html>

// Shared helpers for repair status / urgency styling
export const STATUS_META = {
    PENDING:       { label: 'รอรับเรื่อง',      color: 'rose',    icon: '⏳' },
    ACKNOWLEDGED:  { label: 'รับเรื่องแล้ว',    color: 'orange',  icon: '📋' },
    IN_PROGRESS:   { label: 'กำลังซ่อม',        color: 'amber',   icon: '🔧' },
    WAITING_PARTS: { label: 'รออะไหล่',          color: 'violet',  icon: '📦' },
    OUTSOURCED:    { label: 'ส่งซ่อมภายนอก',    color: 'sky',     icon: '🚚' },
    REPAIRED:      { label: 'ซ่อมเสร็จ',        color: 'teal',    icon: '✅' },
    VERIFIED:      { label: 'ตรวจรับแล้ว',      color: 'emerald', icon: '☑️' },
    CLOSED:        { label: 'ปิดงาน',            color: 'slate',   icon: '📁' },
    CANCELLED:     { label: 'ยกเลิก',            color: 'gray',    icon: '✖' },
};

export const URGENCY_META = {
    CRITICAL: { label: 'วิกฤติ',      color: 'red',     bg: 'bg-red-600 text-white',     ring: 'ring-red-200',     dot: 'bg-red-500',     sla: 'ภายใน 1 ชม.' },
    HIGH:     { label: 'สูง',         color: 'orange',  bg: 'bg-orange-500 text-white',  ring: 'ring-orange-200',  dot: 'bg-orange-500',  sla: 'ภายใน 4 ชม.' },
    MEDIUM:   { label: 'ปานกลาง',     color: 'amber',   bg: 'bg-amber-400 text-white',   ring: 'ring-amber-200',   dot: 'bg-amber-400',   sla: 'ภายใน 24 ชม.' },
    LOW:      { label: 'ต่ำ',         color: 'green',   bg: 'bg-emerald-500 text-white', ring: 'ring-emerald-200', dot: 'bg-emerald-500', sla: 'ภายใน 7 วัน' },
};

// Main linear pipeline (side branches: WAITING_PARTS, OUTSOURCED shown separately)
export const STATUS_PIPELINE = [
    'PENDING',
    'ACKNOWLEDGED',
    'IN_PROGRESS',
    'REPAIRED',
    'VERIFIED',
    'CLOSED',
];

// Statuses that are "side branch" — shown as a tag below the timeline
export const STATUS_SIDE_BRANCH = ['WAITING_PARTS', 'OUTSOURCED'];

export const COLOR_BG = {
    rose:    'bg-rose-100 text-rose-700',
    orange:  'bg-orange-100 text-orange-700',
    amber:   'bg-amber-100 text-amber-700',
    violet:  'bg-violet-100 text-violet-700',
    sky:     'bg-sky-100 text-sky-700',
    teal:    'bg-teal-100 text-teal-700',
    emerald: 'bg-emerald-100 text-emerald-700',
    slate:   'bg-slate-100 text-slate-600',
    gray:    'bg-gray-100 text-gray-500',
};

export function statusBadgeClass(status) {
    const meta = STATUS_META[status];
    return COLOR_BG[meta?.color] ?? COLOR_BG.slate;
}

export function statusLabel(status) {
    return STATUS_META[status]?.label ?? status;
}

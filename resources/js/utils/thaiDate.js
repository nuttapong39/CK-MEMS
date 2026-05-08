import dayjs from 'dayjs';
import 'dayjs/locale/th';

dayjs.locale('th');

const thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
const thaiMonths = [
    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
];

export function formatThaiFullDate(date = new Date()) {
    const d = dayjs(date);
    const day = thaiDays[d.day()];
    const dom = d.date();
    const mon = thaiMonths[d.month()];
    const buddhistYear = d.year() + 543;
    return `วัน${day}ที่ ${dom} ${mon} พ.ศ. ${buddhistYear}`;
}

export function formatThaiShortDate(date = new Date()) {
    const d = dayjs(date);
    const dom = d.date();
    const mon = thaiMonths[d.month()];
    const buddhistYear = d.year() + 543;
    return `${dom} ${mon} ${buddhistYear}`;
}

export function formatThaiTime(date = new Date()) {
    return dayjs(date).format('HH:mm:ss') + ' น.';
}

export function buddhistYear(date = new Date()) {
    return dayjs(date).year() + 543;
}

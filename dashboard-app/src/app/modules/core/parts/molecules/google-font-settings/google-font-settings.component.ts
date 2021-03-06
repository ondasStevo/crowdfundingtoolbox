import {Component, Input, OnChanges, OnInit, ViewChild, ElementRef, EventEmitter, Output} from '@angular/core';
import {delay} from "q";
import {DropdownItem} from "../../../models/dropdown-item";
import {GoogleFontsService} from "../../../services/google-fonts.service";


@Component({
    selector: 'app-google-font-settings',
    templateUrl: './google-font-settings.component.html',
    styleUrls: ['./google-font-settings.component.scss']
})
export class GoogleFontSettingsComponent implements OnInit, OnChanges {

    @ViewChild('fontFamilyList') fontFamilyRef: any;



    public numberOptions: DropdownItem[] = [];
    public fontFamily: any = [];
    public fontFamilyStrings: string[] = [];
    public currentFonts: string = '';

    @Input()
    public selectedValues: string[] = ['Open Sans', 'Lato', 'Oswald'];

    @Output()
    public selectedValuesChange = new EventEmitter<any>();
    public currentOptionValue: string = '';

    constructor(private googleFontsService: GoogleFontsService) {
    }

    ngOnInit() {
        this.initNumberOptions();
        this.fetchGoogleFonts();
    }

    ngOnChanges() {
        console.log(this.selectedValues)
    }

    private fetchGoogleFonts() {
        this.fontFamilyStrings = [];
        this.fontFamily = [];
        this.currentFonts = '';
        this.googleFontsService.getFonts().pipe().subscribe(
            data => {
                if (data.items.length > 0) {
                    data.items.map((d, key) => {
                        if (key < 200) { // only 200 fonts will be showed in list
                            this.fontFamily.push({id: d.family, value: d.family});
                            this.fontFamilyStrings.push(d.family);
                            this.currentFonts += (this.currentFonts == '') ? d.family : '|' + d.family;
                        }
                    });
                    this.selectAction();
                }
            }
        );

    }

    private initNumberOptions() {
        this.numberOptions = [
            {
                title: '5 fonts',
                value: 5
            },
            {
                title: '10 fonts',
                value: 10
            },
            {
                title: '20 fonts',
                value: 20
            },
            {
                title: '30 fonts',
                value: 30
            },
            {
                title: '50 fonts',
                value: 50
            },
            {
                title: '100 fonts',
                value: 100
            },
            {
                title: 'all fonts',
                value: 0
            }

        ];
        this.currentOptionValue = this.numberOptions[1].value;
    }


    public countOfFonts(e) {
        //this.numberGoogleFonts = e;
        this.fetchGoogleFonts();
    }


    public openAction() {
        let children = this.fontFamilyRef.choiceMenuElRef.nativeElement.children;
        for (let ch of children) {
            let currentFontFamily = ch.children[0].children[0].innerText;
            ch.style.fontFamily = currentFontFamily;
        }
    }

    public selectAction() {
        setTimeout(() => {
            // span selected fonts children
            let children = this.fontFamilyRef.mainElRef.nativeElement.children[1].children;
            for (let ch of children) {
                let currentFontFamily = ch.innerText;
                ch.style.fontFamily = currentFontFamily;
            }
        }, 0);
    }

    public emitValues() {
        this.selectedValuesChange.emit(this.fontFamilyRef.optionsSelected)
    }
}
